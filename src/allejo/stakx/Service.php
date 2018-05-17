<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

abstract class Service
{
    protected static $workingDirectory;
    protected static $parameters;

    public static function getParameter($key)
    {
        return self::$parameters[$key];
    }

    public static function setParameter($key, $value)
    {
        return self::$parameters[$key] = $value;
    }

    public static function getWorkingDirectory()
    {
        if (!self::$workingDirectory)
        {
            return getcwd();
        }

        return self::$workingDirectory;
    }

    public static function setWorkingDirectory($directory)
    {
        self::$workingDirectory = $directory;
    }
}
