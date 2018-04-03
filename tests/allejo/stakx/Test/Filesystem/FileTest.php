<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Filesystem;

use allejo\stakx\Filesystem\File;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class FileTest extends PHPUnit_Stakx_TestCase
{
    /** @var string */
    private $absPath;
    /** @var File */
    private $file;

    public function setUp()
    {
        parent::setUp();

        $this->absPath = __DIR__ . '/../assets/ConfigurationFiles/sample.yml';
        $this->file = new File($this->absPath);
    }

    public function testExists()
    {
        $this->assertTrue($this->file->exists());
    }

    public function testGetBasename()
    {
        $this->assertEquals('sample', $this->file->getBasename());
    }

    public function testGetFilename()
    {
        $this->assertEquals('sample.yml', $this->file->getFilename());
    }

    public function testGetAbsolutePath()
    {
        $this->assertEquals(realpath($this->absPath), $this->file->getAbsolutePath());
    }

    public function testGetParentFolder()
    {
        $this->assertEquals(realpath(__DIR__ . '/../assets/ConfigurationFiles'), $this->file->getParentFolder());
    }

    public function testGetRelativeFilePath()
    {
        $path = ['tests', 'allejo/stakx/Test/assets/ConfigurationFiles/sample.yml'];

        $this->assertEquals(implode(DIRECTORY_SEPARATOR, $path), $this->file->getRelativeFilePath());
    }

    public function testGetRelativeParentFolder()
    {
        $path = ['tests', 'allejo', 'stakx', 'Test', 'assets', 'ConfigurationFiles'];

        $this->assertEquals(implode(DIRECTORY_SEPARATOR, $path), $this->file->getRelativeParentFolder());
    }

    public function testGetContents()
    {
        $fileContent = file_get_contents($this->absPath);
        $this->assertEquals($fileContent, $this->file->getContents());
    }

    public function testNonExistentFileReturnsFalse()
    {
        $file = new File('non-existent');

        $this->assertFalse($file->exists());
    }

    public function testGetContentsThrowsErrorOnNonExistentFile()
    {
        $this->setExpectedExceptionRegExp(FileNotFoundException::class, '/.*"non-existent".*/');

        $file = new File('non-existent');
        $file->getContents();
    }
}
