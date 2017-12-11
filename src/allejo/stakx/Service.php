<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

abstract class Service
{
    protected static $parameters;

    public static function getParameter($key)
    {
        return self::$parameters[$key];
    }

    public static function setParameter($key, $value)
    {
        return (self::$parameters[$key] = $value);
    }
}
