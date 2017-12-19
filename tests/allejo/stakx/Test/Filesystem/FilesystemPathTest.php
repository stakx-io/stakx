<?php

namespace allejo\stakx\Test\Filesystem;

use allejo\stakx\Filesystem\FilesystemPath;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class FilesystemPathTest extends PHPUnit_Stakx_TestCase
{
    public function testCannotAppendToFilePath()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $path = new FilesystemPath('/root/bacon');
        $path->appendToPath('vegan');
    }

    public function testFilePathIsDirectory()
    {
        $path = new FilesystemPath('/root/hello/');

        $this->assertTrue($path->isDir(false));
    }

    public function testFilePathAppendDirectory()
    {
        $path = new FilesystemPath('/root/toast/', '/');
        $path->appendToPath('/cheese/');

        $this->assertEquals((string)$path, '/root/toast/cheese/');
    }

    public function testFilePathAppendWindowsDirectory()
    {
        $path = new FilesystemPath('/root/toast/', '/');
        $path->appendToPath('\cheese\\');

        $this->assertEquals((string)$path, '/root/toast/cheese/');
    }

    public function testWindowsFilePathAppendUnix()
    {
        $path = new FilesystemPath('C:\Users\stakx\\', '\\');
        $path->appendToPath('/_site/');

        $this->assertEquals('C:\Users\stakx\_site\\', (string)$path);
    }

    public function testWindowsFilePathAppendWindows()
    {
        $path = new FilesystemPath('C:\Users\stakx\\', '\\');
        $path->appendToPath('\_site\\');

        $this->assertEquals('C:\Users\stakx\_site\\', (string)$path);
    }
}
