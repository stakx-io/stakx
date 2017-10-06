<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

/**
 * The core class to handle reading files from directories on the filesystem.
 *
 * This class is the macOS Finder or Windows Explorer equivalent for stakx. New instances of this class should only be
 * created through the `FileExplorer::create()` helper function. To access the file iterator from this instance, use
 * `FileExplorer::getExplorer()` to retrieve File objects.
 *
 * @internal
 */
class FileExplorer extends \RecursiveFilterIterator implements \Iterator
{
    /**
     * A bitwise flag to have FileExplorer ignore all files unless its been explicitly included; all other files will be
     * ignored.
     */
    const INCLUDE_ONLY_FILES = 0x1;

    /**
     * A bitwise flag to have FileExplorer search files starting with a period as well.
     */
    const ALLOW_DOT_FILES = 0x2;

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
    public static $vcsPatterns = ['.git', '.hg', '.svn', '_svn'];

    /**
     * A list of phrases to exclude from the search.
     *
     * @var string[]
     */
    private $excludes;

    /**
     * A list of phrases to explicitly include in the search.
     *
     * @var string[]
     */
    private $includes;

    /**
     * The bitwise sum of the flags applied to this FileExplorer instance.
     *
     * @var int|null
     */
    private $flags;

    /**
     * FileExplorer constructor.
     *
     * @param \RecursiveIterator $iterator
     * @param string[] $excludes
     * @param string[] $includes
     * @param int|null $flags
     */
    public function __construct(\RecursiveIterator $iterator, array $excludes = array(), array $includes = array(), $flags = null)
    {
        parent::__construct($iterator);

        $this->excludes = array_merge(self::$vcsPatterns, $excludes);
        $this->includes = $includes;
        $this->flags = $flags;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->current()->getFilename();
    }

    /**
     * {@inheritdoc}
     */
    public function accept()
    {
        $filePath = $this->current()->getRelativeFilePath();

        return $this->matchesPattern($filePath);
    }

    /**
     * Get the current File object.
     *
     * @return File
     */
    public function current()
    {
        /** @var \SplFileInfo $current */
        $current = parent::current();

        return new File(
            $current->getPathname(),
            self::getRelativePath($current->getPath()),
            self::getRelativePath($current->getPathname())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return new self(
            $this->getInnerIterator()->getChildren(),
            $this->excludes,
            $this->includes,
            $this->flags
        );
    }

    /**
     * Get an Iterator with all of the files that have met the search requirements.
     *
     * @return \RecursiveIteratorIterator
     */
    public function getExplorer()
    {
        return new \RecursiveIteratorIterator($this);
    }

    /**
     * Check whether or not a relative file path matches the definition given to this FileExplorer instance.
     *
     * @param string $filePath
     *
     * @return bool
     */
    public function matchesPattern($filePath)
    {
        if (self::strpos_array($filePath, $this->includes))
        {
            return true;
        }
        if (($this->flags & self::INCLUDE_ONLY_FILES) && !$this->current()->isDir())
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
     * Create an instance of FileExplorer from a directory path as a string.
     *
     * @param string   $folder The path to the folder we're scanning
     * @param string[] $excludes
     * @param string[] $includes
     * @param int|null $flags
     *
     * @return FileExplorer
     */
    public static function create($folder, $excludes = array(), $includes = array(), $flags = null)
    {
        $folder = self::realpath($folder);
        $iterator = new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS);

        return new self($iterator, $excludes, $includes, $flags);
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
    private static function strpos_array($haystack, $needle, $offset = 0)
    {
        if (!is_array($needle))
        {
            $needle = array($needle);
        }

        foreach ($needle as $query)
        {
            if (substr($query, 0, 1) == '/' && substr($query, -1, 1) == '/' && preg_match($query, $haystack) === 1)
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

    /**
     * Strip the current working directory from an absolute path.
     *
     * @param string $path An absolute path
     *
     * @return string
     */
    private static function getRelativePath($path)
    {
        return str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $path);
    }

    /**
     * A vfsStream friendly way of getting the realpath() of something.
     *
     * @param string $path
     *
     * @return string
     */
    private static function realpath($path)
    {
        if (substr($path, 0, 6) == 'vfs://')
        {
            return $path;
        }

        return realpath($path);
    }
}
