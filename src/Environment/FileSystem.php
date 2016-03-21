<?php

namespace allejo\stakx\Environment;

class Filesystem extends \Symfony\Component\Filesystem\Filesystem
{
    /**
     * @return string
     */
    public function buildPath ()
    {
        return implode(DIRECTORY_SEPARATOR, func_get_args());
    }

    public function getFileName ($filePath)
    {
        return pathinfo($filePath, PATHINFO_BASENAME);
    }

    public function getFolderPath ($filePath)
    {
        return pathinfo($filePath, PATHINFO_DIRNAME);
    }

    /**
     * @param  string $filename A filename
     *
     * @return string The extension of the file
     */
    public function getExtension ($filename)
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    public function writeFile ($targetDir, $fileName, $content)
    {
        $outputFolder = $this->getFolderPath($this->buildPath($targetDir, $fileName));
        $targetFile   = $this->getFileName($fileName);

        if (!file_exists($outputFolder))
        {
            mkdir($outputFolder, 0755, true);
        }

        file_put_contents($this->buildPath($outputFolder, $targetFile), $content, LOCK_EX);
    }
}