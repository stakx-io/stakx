<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

abstract class Service
{
    protected static $workingDirectory;

    protected static $runTimeStatus;

    protected static $options;

    public static function setOption($key, $value): void
    {
        self::$options[$key] = $value;
    }

    public static function getOption($key)
    {
        return self::$options[$key] ?? null;
    }

    public static function hasRunTimeFlag($status)
    {
        return self::$runTimeStatus & $status;
    }

    public static function setRuntimeFlag($status): void
    {
        self::$runTimeStatus |= $status;
    }

    public static function removeRuntimeFlag($status): void
    {
        self::$runTimeStatus &= ~$status;
    }

    public static function resetRuntimeFlags(): void
    {
        self::$runTimeStatus = 0;
    }

    public static function getWorkingDirectory()
    {
        if (!self::$workingDirectory) {
            return getcwd();
        }

        return self::$workingDirectory;
    }

    public static function setWorkingDirectory($directory): void
    {
        self::$workingDirectory = $directory;
    }
}
