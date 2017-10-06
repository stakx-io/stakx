<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

/**
 * A representation of a file on a given filesystem.
 *
 * This class extends \SplFileInfo and adds new methods along with overriding some methods solely because I feel that
 * some of the naming can be misleading.
 */
final class File extends \SplFileInfo
{
    private $relativeParentFolder;
    private $relativeFilePath;

    /**
     * @param string $file_name            The file name
     * @param string $relativeParentFolder The relative path
     * @param string $relativeFilePath     The relative path name
     */
    public function __construct($file_name, $relativeParentFolder, $relativeFilePath)
    {
        parent::__construct($file_name);

        $this->relativeParentFolder = $relativeParentFolder;
        $this->relativeFilePath = $relativeFilePath;
    }

    public function getBasename($suffix = null)
    {
        return parent::getBasename($this->getExtension());
    }

    public function getFilename()
    {
        return $this->getBasename();
    }

    public function getFilePath()
    {
        return $this->getPathname();
    }

    public function getParentFolder()
    {
        return $this->getPath();
    }

    public function getRelativeFilePath()
    {
        return $this->relativeFilePath;
    }

    public function getRelativeParentFolder()
    {
        return $this->relativeParentFolder;
    }

    public function getContents()
    {
        $content = file_get_contents($this->getPathname());

        if ($content === false)
        {
            $error = error_get_last();
            throw new \RuntimeException($error['message']);
        }

        return $content;
    }
}
