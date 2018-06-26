<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

abstract class Service
{
    protected static $workingDirectory;
    protected static $runTimeStatus;
    protected static $parameters;

    public static function hasRunTimeFlag($status)
    {
        return self::$runTimeStatus & $status;
    }

    public static function setRuntimeFlag($status)
    {
        self::$runTimeStatus |= $status;
    }

    public static function resetRuntimeFlags()
    {
        self::$runTimeStatus = 0;
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
