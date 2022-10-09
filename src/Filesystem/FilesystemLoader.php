<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

/**
 * @method static \string        absolutePath(\string ...$pathFragments)       Build an absolute file or directory path separated by the OS specific directory separator.
 * @method static \bool          exists(\string $path)                         Check whether a given file path exists or not.
 * @method static \string        getExtension(\string $path)                   Get the extension of a given file.
 * @method static \string        getInternalResource(\string $file)            Get the contents of a stakx resource file.
 * @method static \string        getFolderPath(\string $path)                  Get the parent directory of a given file.
 * @method static \string        getRelativePath(\string|FilesystemPath $path) Strip the current working directory from an absolute path.
 * @method static \bool          isVFS(\string $path)                          Check whether a given path is on the virtual filesystem.
 * @method static FilesystemPath path(\string $path)                           Build a cross-platform ready filesystem path.
 * @method static false|string   realpath(\string $path)                       A vfsStream friendly way of getting the realpath() of something.
 * @method static \string        removeExtension(\string $path)                Get the full path to the file without the extension.
 */
abstract class FilesystemLoader
{
    private static ?Filesystem $fs = null;

    public static function __callStatic($name, $arguments)
    {
        if (self::$fs === null) {
            self::$fs = new Filesystem();
        }

        return call_user_func_array([self::$fs, $name], $arguments);
    }
}
