<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Utilities;

class StrUtils
{
    /**
     * Interpolates context values into the message placeholders.
     *
     * @author PHP Framework Interoperability Group
     *
     * @param string $message
     * @param array  $context
     *
     * @return string
     */
    public static function interpolate($message, array $context)
    {
        // build a replacement array with braces around the context keys
        $replace = array();

        foreach ($context as $key => $val)
        {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString')))
            {
                $replace[sprintf('{%s}', $key)] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}