<?php

class Logger
{
    public static function verbose($enabled, $message, $args)
    {
        $arguments = func_get_args();
        $enabled = array_shift($arguments);

        if ($enabled)
        {
            call_user_func_array('sprintf', $arguments);
        }
    }
}