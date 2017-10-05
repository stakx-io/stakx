<?php

namespace allejo\stakx\Test\Filesystem;

use allejo\stakx\Filesystem\FSPath;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use org\bovigo\vfs\vfsStream;

class FSPathTest extends PHPUnit_Stakx_TestCase
{
    public function testCannotAppendToFilePath()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $this->rootDir->addChild(vfsStream::newFile('bacon'));

        $path = new FSPath('vfs://root/bacon');
        $path->appendToPath('vegan');
    }

    public function testFilePathIsDirectory()
    {
        $this->rootDir->addChild(vfsStream::newDirectory('hello'));

        $path = new FSPath('vfs://root/hello');

        $this->assertTrue($path->isDir());
    }

    public function testFilePathAppendDirectory()
    {
        $this->rootDir->addChild(vfsStream::newDirectory('toast'));

        $path = new FSPath('vfs://root/toast');
        $path->appendToPath('/cheese/');

        $this->assertEquals((string)$path, 'vfs://root/toast/cheese/');
    }

}
