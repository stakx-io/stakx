<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test;

/**
 * Class StreamIntercept.
 *
 * @see http://stackoverflow.com/a/39785995
 */
class StreamInterceptor extends \php_user_filter
{
    public static $output = '';

    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in))
        {
            self::$output .= $bucket->data;
            $consumed += $bucket->datalen;
        }

        return PSFS_PASS_ON;
    }
}
