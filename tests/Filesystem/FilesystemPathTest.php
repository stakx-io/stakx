<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Filesystem;

use allejo\stakx\Filesystem\FilesystemPath;
use allejo\stakx\Test\StakxTestCase;
use InvalidArgumentException;

/**
 * @internal
 *
 * @coversNothing
 */
class FilesystemPathTest extends StakxTestCase
{
    public function testCannotAppendToFilePath(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $path = new FilesystemPath('/root/bacon');
        $path->appendToPath('vegan');
    }

    public function testFilePathIsDirectory(): void
    {
        $path = new FilesystemPath('/root/hello/');

        $this->assertTrue($path->isDir(false));
    }

    public function testFilePathAppendDirectory(): void
    {
        $path = new FilesystemPath('/root/toast/', '/');
        $path->appendToPath('/cheese/');

        $this->assertEquals('/root/toast/cheese/', (string)$path);
    }

    public function testFilePathAppendWindowsDirectory(): void
    {
        $path = new FilesystemPath('/root/toast/', '/');
        $path->appendToPath('\cheese\\');

        $this->assertEquals('/root/toast/cheese/', (string)$path);
    }

    public function testWindowsFilePathAppendUnix(): void
    {
        $path = new FilesystemPath('C:\Users\stakx\\', '\\');
        $path->appendToPath('/_site/');

        $this->assertEquals('C:\Users\stakx\_site\\', (string)$path);
    }

    public function testWindowsFilePathAppendWindows(): void
    {
        $path = new FilesystemPath('C:\Users\stakx\\', '\\');
        $path->appendToPath('\_site\\');

        $this->assertEquals('C:\Users\stakx\_site\\', (string)$path);
    }
}
