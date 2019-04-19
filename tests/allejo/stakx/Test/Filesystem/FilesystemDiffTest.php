<?php

namespace allejo\stakx\Test\Filesystem;

use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FileExplorerDefinition;
use allejo\stakx\Filesystem\FilesystemDiff;
use allejo\stakx\Filesystem\Folder;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamAbstractContent;

class FilesystemDiffTest extends PHPUnit_Stakx_TestCase
{
    public function testShouldReturnDiffFromFlatFolder()
    {
        $folderDef = $this->createStructure([
            'old-file-1' => new \DateTime('2019-01-01 00:00'),
            'old-file-2' => new \DateTime('2019-01-02 00:00'),
            'changed-file' => new \DateTime('2019-02-01 00:00'),
        ]);

        $fsDiff = new FilesystemDiff([
            $folderDef
        ]);

        $diff = $fsDiff->modifiedAfter(new \DateTime('2019-01-15 00:00'));

        $this->assertCount(1, $diff);
        $this->assertEquals('changed-file', $diff[0]->getFilename());
        $this->assertInstanceOf(File::class, $diff[0]);
    }

    public function testShouldReturnDiffFromNestedFolder()
    {
        $folderDef = $this->createStructure([
            'old-file' => new \DateTime('2019-01-01 00:00'),
            'folder' => [
                'alt-old-file' => new \DateTime('2019-01-10 00:00'),
                'changed-file' => new \DateTime('2019-02-01 00:00'),
            ],
        ]);

        $fsDiff = new FilesystemDiff([
            $folderDef,
        ]);

        $diff = $fsDiff->modifiedAfter(new \DateTime('2019-01-15 00:00'));

        $this->assertCount(1, $diff);
        $this->assertEquals($this->rootDir->getChild('folder/changed-file')->url(), $diff[0]->getAbsolutePath());
        $this->assertInstanceOf(File::class, $diff[0]);
    }

    /**
     * @param array                         $folderDefs
     * @param vfsStreamAbstractContent|null $parent
     *
     * @return FileExplorerDefinition
     */
    private function createStructure(array $folderDefs, vfsStreamAbstractContent $parent = null)
    {
        /**
         * @var string          $name
         * @var \DateTime|array $lastModified
         */
        foreach ($folderDefs as $name => $lastModified)
        {
            $target = $parent === null ? $this->rootDir : $parent;

            if (is_array($lastModified))
            {
                $location = vfsStream::newDirectory($name)->at($target);

                $this->createStructure($lastModified, $location);
                continue;
            }

            $vFile = vfsStream::newFile($name)->at($target);

            touch(
                $vFile->url(),
                $lastModified->getTimestamp()
            );
        }

        return new FileExplorerDefinition(new Folder($this->rootDir->url()));
    }
}
