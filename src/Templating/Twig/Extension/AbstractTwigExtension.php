<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

abstract class AbstractTwigExtension
{
    /**
     * Whether or not this filter is allowed when a site is built in safe mode.
     *
     * @return bool
     */
    public static function disableInSafeMode()
    {
        return false;
    }

    /**
     * Whether or not this filter is allowed when a site is built in debug mode.
     *
     * @return bool
     */
    public static function onlyInDebug()
    {
        return false;
    }
}
