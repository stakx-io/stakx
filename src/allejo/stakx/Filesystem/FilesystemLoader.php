<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

use allejo\stakx\System\Filesystem;

/**
 * @method static string absolutePath(string ...$pathFragments) Build an absolute file or directory path separated by the OS specific directory separator.
 * @method static bool exists(string $path) Check whether a given file path exists or not.
 * @method static string getExtension(string $path) Get the extension of a given file.
 * @method static string getFolderPath(string $path) Get the parent directory of a given file.
 * @method static string getRelativePath(string $path) Strip the current working directory from an absolute path.
 * @method static FilesystemPath path(string $path) Build a cross-platform ready filesystem path.
 * @method static string removeExtension(string $path) Get the full path to the file without the extension.
 */
abstract class FilesystemLoader
{
    private static $fs;

    public static function __callStatic($name, $arguments)
    {
        if (self::$fs === null)
        {
            self::$fs = new Filesystem();
        }

        return call_user_func_array([self::$fs, $name], $arguments);
    }
}
