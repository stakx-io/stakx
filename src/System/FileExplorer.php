<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\System;

class FileExplorer extends \RecursiveFilterIterator
{
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
     * FileExplorer constructor.
     *
     * @param \RecursiveIterator $iterator
     * @param array              $excludes
     * @param array              $includes
     */
    public function __construct(\RecursiveIterator $iterator, array $excludes = array(), array $includes = array())
    {
        parent::__construct($iterator);

        $this->excludes = array_merge(self::$vcsPatterns, $excludes);
        $this->includes = $includes;
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
        if (preg_match('#(^|/)\..+(/|$)#', $filePath) === 1) { return false; }

        return ($this->strpos_array($filePath, $this->excludes) === false);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return (new self(
            $this->getInnerIterator()->getChildren(),
            $this->excludes,
            $this->includes
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
     *
     * @return FileExplorer
     */
    public static function create ($folder, $excludes = array(), $includes = array())
    {
        $iterator = new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS);

        return (new self($iterator, $excludes, $includes));
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
}
