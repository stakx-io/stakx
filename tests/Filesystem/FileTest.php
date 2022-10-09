<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Filesystem;

use allejo\stakx\Filesystem\File;
use allejo\stakx\Test\StakxTestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * @internal
 *
 * @covers \allejo\stakx\Filesystem\File
 */
class FileTest extends StakxTestCase
{
    private string $absPath;

    private File $file;

    public function setUp(): void
    {
        parent::setUp();

        $this->absPath = __DIR__ . '/../assets/ConfigurationFiles/sample.yml';
        $this->file = new File($this->absPath);
    }

    public function testExists(): void
    {
        $this->assertTrue($this->file->exists());
    }

    public function testGetBasename(): void
    {
        $this->assertEquals('sample', $this->file->getBasename());
    }

    public function testGetFilename(): void
    {
        $this->assertEquals('sample.yml', $this->file->getFilename());
    }

    public function testGetAbsolutePath(): void
    {
        $this->assertEquals(realpath($this->absPath), $this->file->getAbsolutePath());
    }

    public function testGetParentFolder(): void
    {
        $this->assertEquals(realpath(__DIR__ . '/../assets/ConfigurationFiles'), $this->file->getParentFolder());
    }

    public function testGetRelativeFilePath(): void
    {
        $path = ['tests', 'assets', 'ConfigurationFiles', 'sample.yml'];

        $this->assertEquals(implode(DIRECTORY_SEPARATOR, $path), $this->file->getRelativeFilePath());
    }

    public function testGetRelativeParentFolder(): void
    {
        $path = ['tests', 'assets', 'ConfigurationFiles'];

        $this->assertEquals(implode(DIRECTORY_SEPARATOR, $path), $this->file->getRelativeParentFolder());
    }

    public function testGetContents(): void
    {
        $fileContent = file_get_contents($this->absPath);
        $this->assertEquals($fileContent, $this->file->getContents());
    }

    public function testGetContentsThrowsErrorOnNonExistentFile(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessageMatches('/.*"non-existent".*/');

        new File('non-existent');
    }
}
