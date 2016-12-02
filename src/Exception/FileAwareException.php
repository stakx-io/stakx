<?php

namespace allejo\stakx\Exception;

class FileAwareException extends \Exception
{
    private $fileLocation;

    /**
     * @return mixed
     */
    public function getFileLocation ()
    {
        return $this->fileLocation;
    }

    /**
     * @param mixed $fileLocation
     */
    public function setFileLocation ($fileLocation)
    {
        $this->fileLocation = $fileLocation;
    }
}