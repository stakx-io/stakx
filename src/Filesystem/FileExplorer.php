<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

use allejo\stakx\Filesystem\FilesystemLoader as fs;

/**
 * The core class to handle reading files from directories on the filesystem.
 *
 * This class is the macOS Finder or Windows Explorer equivalent for stakx. New instances of this class should only be
 * created through the `FileExplorer::create()` helper function. To access the file iterator from this instance, use
 * `FileExplorer::getFileIterator()` to retrieve File objects.
 *
 * @internal
 */
class FileExplorer extends \RecursiveFilterIterator implements \Iterator
{
    /**
     * A bitwise flag to have FileExplorer ignore all files unless its been explicitly included; all other files will be
     * ignored.
     */
    public const INCLUDE_ONLY_FILES = 0x1;

    /**
     * A bitwise flag to have FileExplorer search files starting with a period as well.
     */
    public const ALLOW_DOT_FILES = 0x2;

    /**
     * A bitwise flag to have FileExplorer ignore any directories.
     */
    public const IGNORE_DIRECTORIES = 0x4;

    /**
     * A list of common version control folders to ignore.
     *
     * The following folders should be ignored explicitly by the end user. Their usage isn't as popular so adding more
     * conditions to loop through will only slow down FileExplorer.
     *
     *   - 'CVS'
     *   - '_darcs'
     *   - '.arch-params'
     *   - '.monotone'
     *   - '.bzr'
     *
     * @var string[]
     */
    public static array $vcsPatterns = ['.git', '.hg', '.svn', '_svn'];

    /**
     * A custom callable that will be used in the `accept()` method. If null, the default matcher will be used.
     *
     * @var callable[]
     */
    private array $matchers = [];

    /**
     * A list of phrases to exclude from the search.
     *
     * @var string[]
     */
    private array $excludes;

    /**
     * FileExplorer constructor.
     *
     * @param \RecursiveIterator $iterator
     * @param string[]           $includes A list of phrases to explicitly include in the search.
     * @param string[]           $excludes
     * @param int|null           $flags    The bitwise sum of the flags applied to this FileExplorer instance.
     */
    public function __construct(\RecursiveIterator $iterator, private readonly array $includes = [], array $excludes = [], private readonly ?int $flags = null)
    {
        parent::__construct($iterator);

        $this->excludes = array_merge(self::$vcsPatterns, $excludes);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->current()->getFilename();
    }

    public function accept(): bool
    {
        if (!empty($this->matchers))
        {
            foreach ($this->matchers as $matcher)
            {
                $result = call_user_func($matcher, $this->current());

                // If any custom matchers return false, let's exit immediately
                if ($result === false)
                {
                    return false;
                }
            }
        }

        $filePath = $this->current()->getRelativeFilePath();

        return $this->matchesPattern($filePath);
    }

    /**
     * Get the current File object.
     */
    public function current(): File|Folder
    {
        /** @var \SplFileInfo $current */
        $current = parent::current();
        $path = new FilesystemPath($current->getPathname());

        if ($current->isDir())
        {
            return new Folder($path);
        }

        return new File($path);
    }

    public function getChildren(): FileExplorer|\RecursiveFilterIterator|null
    {
        $explorer = new self(
            $this->getInnerIterator()->getChildren(), $this->includes, $this->excludes, $this->flags
        );

        foreach ($this->matchers as $matcher)
        {
            $explorer->addMatcher($matcher);
        }

        return $explorer;
    }

    /**
     * Get an Iterator with all the files (and *only* files) that have met the search requirements.
     */
    public function getFileIterator(): \RecursiveIteratorIterator
    {
        return new \RecursiveIteratorIterator($this);
    }

    /**
     * Check whether a relative file path matches the definition given to this FileExplorer instance.
     */
    public function matchesPattern(string $filePath): bool
    {
        if (self::strpos_array($filePath, $this->includes))
        {
            return true;
        }
        if (($this->flags & self::INCLUDE_ONLY_FILES) && !$this->current()->isDir())
        {
            return false;
        }
        if (($this->flags & self::IGNORE_DIRECTORIES) && $this->current()->isDir())
        {
            return false;
        }

        if (!($this->flags & self::ALLOW_DOT_FILES) &&
            preg_match('#(^|\\\\|\/)\..+(\\\\|\/|$)#', $filePath) === 1)
        {
            return false;
        }

        return self::strpos_array($filePath, $this->excludes) === false;
    }

    /**
     * Add a custom matcher that will be executed before the default matcher that uses file names and paths.
     */
    public function addMatcher(callable $callable): void
    {
        $this->matchers[] = $callable;
    }

    /**
     * Create an instance of FileExplorer from a directory path as a string.
     *
     * @deprecated Use `FileExplorer::createFromDefinition()` instead.
     *
     * @param string $folder The path to the folder we're scanning
     * @param string[] $includes
     * @param string[] $excludes
     * @param int|null $flags
     *
     * @return FileExplorer
     */
    public static function create(string $folder, array $includes = [], array $excludes = [], ?int $flags = null): FileExplorer
    {
        $folder = fs::realpath($folder);
        $iterator = new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS);

        return new self($iterator, $includes, $excludes, $flags);
    }

    public static function createFromDefinition(FileExplorerDefinition $definition): FileExplorer
    {
        return self::create($definition->folder, $definition->includes, $definition->excludes, $definition->flags);
    }

    /**
     * Search a given string for an array of possible elements.
     *
     * @param string   $haystack
     * @param string[] $needle
     * @param int      $offset
     *
     * @return bool True if an element from the given array was found in the string
     */
    private static function strpos_array(string $haystack, array $needle, int $offset = 0): bool
    {
        if (!is_array($needle))
        {
            $needle = [$needle];
        }

        foreach ($needle as $query)
        {
            if (str_starts_with($query, '/') && substr($query, -1, 1) === '/' && preg_match($query, $haystack) === 1)
            {
                return true;
            }

            if (strpos($haystack, $query, $offset) !== false)
            { // stop on first true result
                return true;
            }
        }

        return false;
    }
}
