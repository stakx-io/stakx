<?php

namespace allejo\stakx\Exception;

use Symfony\Component\Finder\SplFileInfo;

/**
 * Class FileAwareException
 *
 * These functions are not to be confused with getFile() which tracks the source file that triggered this exception,
 * where as these functions will keep track of what website file triggered the exception; e.g. a Twig template or
 * invalid JSON file
 *
 * @package allejo\stakx\Exception
 */
class FileAwareException extends \Exception
{
    /**
     * @var string|SplFileInfo
     */
    private $fileLocation;

    /**
     * Get the location of the website file that triggered this exception
     *
     * @return string
     */
    public function getFileLocation ()
    {
        return $this->fileLocation;
    }

    /**
     * Set the location of the website file that triggered this exception
     *
     * @param string|SplFileInfo $fileLocation
     */
    public function setFileLocation ($fileLocation)
    {
        $this->fileLocation = $fileLocation;
    }
}