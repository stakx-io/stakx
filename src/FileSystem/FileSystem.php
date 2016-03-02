<?php

namespace allejo\stakx\FileSystem;

class FileSystem extends \Symfony\Component\Filesystem\Filesystem
{
    /**
     * Finds path, relative to the given root folder, of all files and directories in the given directory and its
     * sub-directories non recursively.
     *
     * @author sreekumar
     *
     * @param  string $root The location where to search
     *
     * @return array  A nested array with two associative keys, 'files' & 'dirs'
     */
    public function ls ($root = '.')
    {
        $files  = array('files' => array(), 'dirs' => array());
        $directories  = array();
        $last_letter  = $root[strlen($root) - 1];
        $root  = ($last_letter == '\\' || $last_letter == '/') ? $root : $root . DIRECTORY_SEPARATOR;

        $directories[]  = $root;

        while (sizeof($directories))
        {
            $dir  = array_pop($directories);

            if ($handle = opendir($dir))
            {
                while (false !== ($file = readdir($handle)))
                {
                    if ($file == '.' || $file == '..')
                    {
                        continue;
                    }

                    $file  = $dir.$file;

                    if (is_dir($file))
                    {
                        $directory_path = $file . DIRECTORY_SEPARATOR;
                        array_push($directories, $directory_path);
                        $files['dirs'][]  = $directory_path;
                    }
                    elseif (is_file($file))
                    {
                        $files['files'][]  = $file;
                    }
                }
                closedir($handle);
            }
        }

        return $files;
    }
}