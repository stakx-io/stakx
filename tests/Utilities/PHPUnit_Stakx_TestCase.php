<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

abstract class PHPUnit_Stakx_TestCase extends PHPUnit_Framework_TestCase
{
    public function assertFileExistsAndContains ($filePath, $needle, $message = '')
    {
        $this->assertFileExists($filePath, $message);

        $contents = file_get_contents($filePath);

        $this->assertContains($needle, $contents, $message);
    }
}