<?php

require __DIR__ . "/../vendor/autoload.php";

// The only deprecation warnings we need to ignore/handle are in PHP 7.4 so far
if (PHP_VERSION_ID >= 70400) {
    function customErrorHandler($errno, $errstr, $errfile, $errline) {
        // We know about this deprecation warning exists and it's already been
        // fixed in the 2.x branch. For BC reasons in the 1.x branch, we'll
        // ignore this warning to let tests pass.
        if ($errno === E_DEPRECATED) {
            if ($errstr === "Function ReflectionType::__toString() is deprecated") {
                return true;
            }
        }

        // Any other error should be left up to PHPUnit to handle
        return \PHPUnit_Util_ErrorHandler::handleError($errno, $errstr, $errfile, $errline);
    }

    set_error_handler("customErrorHandler");
}

define('STAKX_TEST_ROOT', __DIR__);
define('STAKX_PROJ_ROOT', dirname(__DIR__));
