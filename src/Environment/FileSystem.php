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

    /**
     * Finds path, relative to the given root folder, of all files and directories in the given directory and its
     * sub-directories non recursively.
     *
     * **Note** The majority of this function was written by sreekumar but has been modified to include more
     * functionality specific to this project.
     *
     * @link  http://php.net/manual/en/function.readdir.php#103418 See original function
     *
     * @param  string $root        The location where to search
     * @param  array  $ignoreDirs  An array of folders to ignore
     * @param  array  $ignoreRegex An array of regular expressions to ignore
     * @param  bool   $recursive   Whether or not to list files/folders recursively. When set to false, this will only
     *                             list the files and folders in the specified directory but not the contents of those
     *                             folders.
     *
     * @return array A nested array with two associative keys, 'files' & 'dirs'
     */
    public function ls ($root = '.', $ignoreDirs = array(), $ignoreRegex = array(), $recursive = true)
    {
        $fileStructure = array('files' => array(), 'dirs' => array());
        $directories   = array();
        $last_letter   = $root[strlen($root) - 1];
        $root  = ($last_letter == '\\' || $last_letter == '/') ? $root : $root . DIRECTORY_SEPARATOR;

        $directories[] = $root;
        $regexPatterns = array();
        $checkIgnoredDirs  = count($ignoreDirs) > 0;
        $checkRegexIgnores = count($ignoreRegex) > 0;

        foreach ($ignoreRegex as $regex)
        {
            $regexPatterns[] = sprintf('(%s)', $regex);
        }

        $masterRegexIgnore = implode('|', $regexPatterns);

        while (sizeof($directories))
        {
            $dir = array_pop($directories);

            if ($handle = opendir($dir))
            {
                while (false !== ($file = readdir($handle)))
                {
                    if (($file[0] === ".") ||
                        ($checkIgnoredDirs  && in_array($file, $ignoreDirs)) ||
                        ($checkRegexIgnores && preg_match($masterRegexIgnore, $file)))
                    {
                        continue;
                    }

                    $file  = $dir . $file;

                    if (is_dir($file))
                    {
                        $directory_path = $file . DIRECTORY_SEPARATOR;
                        $fileStructure['dirs'][]  = $directory_path;

                        if ($recursive)
                        {
                            array_push($directories, $directory_path);
                        }
                    }
                    elseif (is_file($file))
                    {
                        $fileStructure['files'][]  = $file;
                    }
                }

                closedir($handle);
            }
        }

        return $fileStructure;
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