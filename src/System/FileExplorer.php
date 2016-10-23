<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\System;

use Symfony\Component\Finder\SplFileInfo;

class FileExplorer extends \RecursiveFilterIterator
{
    /**
     * A bitwise flag to have FileExplorer ignore all files unless its been explicitly included; all other files will be
     * ignored.
     */
    const INCLUDE_ONLY_FILES = 0x1;

    /**
     * A bitwise flag to have FileExplorer search files starting with a period as well
     */
    const ALLOW_DOT_FILES    = 0x2;

    /**
     * A list of version control folders to ignore
     *
     * @var string[]
     */
    public static $vcsPatterns =  array('.git', '.hg', '.svn', '_svn', 'CVS', '_darcs', '.arch-params', '.monotone', '.bzr');

    /**
     * A list of phrases to exclude from the search
     *
     * @var string[]
     */
    private $excludes;

    /**
     * A list of phrases to explicitly include in the search
     *
     * @var string[]
     */
    private $includes;

    /**
     * The bitwise sum of the flags applied to this FileExplorer instance
     *
     * @var int|null
     */
    private $flags;

    /**
     * FileExplorer constructor.
     *
     * @param \RecursiveIterator $iterator
     * @param array              $excludes
     * @param array              $includes
     * @param int|null           $flags
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
    public function accept ()
    {
        $filePath = str_replace(getcwd() . '/', '', $this->current()->getPathname());

        if ($this->strpos_array($filePath, $this->includes)) { return true; }
        if (($this->flags & self::INCLUDE_ONLY_FILES) && !$this->current()->isDir()) { return false; }

        if (!($this->flags & self::ALLOW_DOT_FILES) &&
            preg_match('#(^|/)\..+(/|$)#', $filePath) === 1) { return false; }

        return ($this->strpos_array($filePath, $this->excludes) === false);
    }

    /**
     * Get the current SplFileInfo object
     *
     * @return SplFileInfo
     */
    public function current()
    {
        /** @var \SplFileInfo $current */
        $current = parent::current();

        return (new SplFileInfo(
            $current->getPathname(),
            $this->getRelativePath($current->getPath()),
            $this->getRelativePath($current->getPathname())
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return (new self(
            $this->getInnerIterator()->getChildren(),
            $this->excludes,
            $this->includes,
            $this->flags
        ));
    }

    /**
     * Get an Iterator with all of the files that have met the search requirements
     *
     * @return \RecursiveIteratorIterator
     */
    public function getExplorer ()
    {
        return (new \RecursiveIteratorIterator($this));
    }

    /**
     * Create an instance of FileExplorer from a directory path as a string
     *
     * @param  string   $folder   The path to the folder we're scanning
     * @param  string[] $excludes
     * @param  string[] $includes
     * @param  int|null $flags
     *
     * @return FileExplorer
     */
    public static function create ($folder, $excludes = array(), $includes = array(), $flags = null)
    {
        $iterator = new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS);

        return (new self($iterator, $excludes, $includes, $flags));
    }

    /**
     * Search a given string for an array of possible elements
     *
     * @param  string   $haystack
     * @param  string[] $needle
     * @param  int      $offset
     *
     * @return bool True if an element from the given array was found in the string
     */
    private function strpos_array ($haystack, $needle, $offset = 0)
    {
        if (!is_array($needle))
        {
            $needle = array($needle);
        }

        foreach ($needle as $query)
        {
            if (strpos($haystack, $query, $offset) !== false) // stop on first true result
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Strip the current working directory from an absolute path
     *
     * @param  string $path An absolute path
     *
     * @return string
     */
    private function getRelativePath ($path)
    {
        return str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $path);
    }
}
