<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Filesystem;

use allejo\stakx\Filesystem\File;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class FileTest extends PHPUnit_Stakx_TestCase
{
    /** @var File */
    private $file;

    public function setUp()
    {
        parent::setUp();

        $this->dummyFile
            ->at($this->rootDir)
            ->setContent('hello world')
        ;

        $this->file = new File($this->dummyFile->url(), 'vfs://');
    }

    public function testGetBasename()
    {
        $this->assertEquals('stakx.html', $this->file->getBasename());
    }

    public function testGetFilename()
    {
        $this->assertEquals('stakx.html.twig', $this->file->getFilename());
    }

    public function testGetAbsolutePath()
    {
        $this->assertEquals('vfs://root/stakx.html.twig', $this->file->getAbsolutePath());
    }

    public function testGetParentFolder()
    {
        $this->assertEquals('vfs://root', $this->file->getParentFolder());
    }

    public function testGetRelativeFilePath()
    {
        $this->assertEquals('root/stakx.html.twig', $this->file->getRelativeFilePath());
    }

    public function testGetRelativeParentFolder()
    {
        $this->assertEquals('root', $this->file->getRelativeParentFolder());
    }

    public function testGetContents()
    {
        $this->assertEquals('hello world', $this->file->getContents());
    }
}
