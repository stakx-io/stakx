<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\System;

class FileExplorer extends \RecursiveFilterIterator
{
    private $excludes;
    private $includes;

    /**
     * FileExplorer constructor.
     *
     * @param \RecursiveIterator $iterator
     * @param array              $excludes
     */
    public function __construct(\RecursiveIterator $iterator, array $excludes = array())
    {
        parent::__construct($iterator);

        // I can't get this to work without hard coding it
        $this->excludes = $excludes;
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
        return $this->strpos_array($this->current()->getPathname(), $this->excludes) === false;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return new self($this->getInnerIterator()->getChildren(), $this->excludes);
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
     * @param  string $folder   The path to the folder we're scanning
     * @param  array  $excludes An array of
     *
     * @return FileExplorer
     */
    public static function create ($folder, $excludes = array())
    {
        $iterator = new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS);

        return (new self($iterator, $excludes));
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
    private function strpos_array($haystack, $needle, $offset=0)
    {
        if (!is_array($needle))
        {
            $needle = array($needle);
        }

        foreach($needle as $query)
        {
            if (strpos($haystack, $query, $offset) !== false) // stop on first true result
            {
                return true;
            }
        }

        return false;
    }
}
