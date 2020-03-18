<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Filesystem;

use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FileExplorer;
use allejo\stakx\Filesystem\FileExplorerMatcher;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use bovigo\vfs\vfsStream;

class FileExplorerTest extends PHPUnit_Stakx_TestCase
{
    public function testFindAllFilesDefaultBehavior()
    {
        $filesystem = [
            '_config.yml' => '',
            'README.md' => '',
            'LICENSE.MD' => '',
        ];

        vfsStream::create($filesystem);

        $explorer = FileExplorer::create($this->rootDir->url())->getFileIterator();
        $count = 0;

        foreach ($explorer as $file)
        {
            $this->assertArrayHasKey($file->getFilename(), $filesystem);
            ++$count;
        }

        $this->assertEquals(count($filesystem), $count);
    }

    public function testDotFilesNotIncludedByDefault()
    {
        $filesystem = [
            '.cache' => '',
            '.htaccess' => '',
            '.something' => '',
            'README.md' => '',
        ];

        vfsStream::create($filesystem);

        $explorer = FileExplorer::create($this->rootDir->url())->getFileIterator();

        foreach ($explorer as $file)
        {
            $this->assertNotEquals('.', $file->getFilename()[0]);
        }
    }

    public function testDotFilesIncludedWithFlag()
    {
        $filesystem = [
            '.cache' => '',
            '.htaccess' => '',
            '.something' => '',
            'README.md' => '',
        ];

        vfsStream::create($filesystem);

        $explorer = FileExplorer::create($this->rootDir->url(), [], [], FileExplorer::ALLOW_DOT_FILES)->getFileIterator();

        $this->assertCount(count($filesystem), $explorer);
    }

    public function testIncludeOnlyFilesEmpty()
    {
        $filesystem = [
            '.htaccess' => '',
            '_config.yml' => '',
            'README.md' => '',
            'LICENSE.MD' => '',
        ];

        vfsStream::create($filesystem);

        $explorer = FileExplorer::create($this->rootDir->url(), [], [], FileExplorer::INCLUDE_ONLY_FILES)->getFileIterator();

        $this->assertCount(0, $explorer);
    }

    public function testIncludeOnlyFiles()
    {
        $filesystem = [
            '.htaccess' => '',
            '_config.yml' => '',
            'README.md' => '',
            'LICENSE.MD' => '',
        ];
        $includes = [
            '.htaccess',
            'README.md',
        ];

        vfsStream::create($filesystem);

        $explorer = FileExplorer::create($this->rootDir->url(), $includes, [], FileExplorer::INCLUDE_ONLY_FILES)->getFileIterator();

        foreach ($explorer as $file)
        {
            $this->assertContains($file->getFilename(), $includes);
        }

        $this->assertCount(count($includes), $explorer);
    }

    public function testExcludingFiles()
    {
        $filesystem = [
            '_config.yml' => '',
            'README.md' => '',
            'LICENSE.md' => '',
            'index.html' => '',
            'feed.xml' => '',
        ];
        $exclusion = [
            'README.md',
            'index.html',
        ];

        vfsStream::create($filesystem);

        $explorer = FileExplorer::create($this->rootDir->url(), [], $exclusion)->getFileIterator();

        foreach ($explorer as $file)
        {
            $this->assertNotContains($file->getFilename(), $exclusion);
        }

        $this->assertCount(count($filesystem) - count($exclusion), $explorer);
    }

    public function testFindingDirectories()
    {
        $filesystem = [
            '_posts' => [],
            'README.md' => '',
        ];

        vfsStream::create($filesystem);

        $explorer = FileExplorer::create($this->rootDir->url());

        foreach ($explorer as $file)
        {
            if ($file->getFilename() === '_posts')
            {
                $this->assertTrue($file->isDir());
            }
            else
            {
                $this->assertTrue($file->isFile());
            }
        }

        $this->assertCount(count($filesystem), $explorer);
    }

    public function testIgnoringDirectories()
    {
        $filesystem = [
            '_posts' => [],
            'README.md' => '',
        ];

        vfsStream::create($filesystem);

        $explorer = FileExplorer::create($this->rootDir->url(), [], [], FileExplorer::IGNORE_DIRECTORIES)->getFileIterator();

        foreach ($explorer as $file)
        {
            $this->assertTrue($file->isFile());
        }

        $this->assertCount(1, $explorer);
    }

    public function testFindingFilesInSubdirectories()
    {
        $filesystem = [
            '_posts' => [
                'hello-world.md' => '',
                'about-me.md' => '',
            ],
            'README.md' => '',
        ];

        vfsStream::create($filesystem);

        $explorer = FileExplorer::create($this->rootDir->url())->getFileIterator();

        foreach ($explorer as $file)
        {
            $this->assertTrue($file->isFile());
        }

        $this->assertCount(3, $explorer);
    }

    public function testExcludingPatterns()
    {
        $excluded = [
            'index.html.twig' => '',
            'about.html.twig' => '',
            'contact.html.twig' => '',
        ];
        $baseFiles = [
            'README.md' => '',
            'LICENSE.md' => '',
        ];

        vfsStream::create(array_merge($excluded, $baseFiles));

        $explorer = FileExplorer::create($this->rootDir->url(), [], ['/\.twig$/'])->getFileIterator();

        foreach ($explorer as $file)
        {
            $this->assertArrayHasKey($file->getFilename(), $baseFiles);
        }

        $this->assertCount(count($baseFiles), $explorer);
    }

    public function testIncludingPatternsOnly()
    {
        $included = [
            'index.html.twig' => '',
            'about.html.twig' => '',
            'contact.html.twig' => '',
        ];
        $baseFiles = [
            'README.md' => '',
            'LICENSE.md' => '',
        ];

        vfsStream::create(array_merge($included, $baseFiles));

        $explorer = FileExplorer::create($this->rootDir->url(), ['/\.twig$/'], [], FileExplorer::INCLUDE_ONLY_FILES)->getFileIterator();

        foreach ($explorer as $file)
        {
            $this->assertArrayNotHasKey($file->getFilename(), $baseFiles);
        }

        $this->assertCount(count($included), $explorer);
    }

    public function testCustomTimestampMatcher()
    {
        $cutoff = new \DateTime('2018-12-01');
        $filesystem = [
            'future.txt' => new \DateTime('2032-01-01'),
            'new.txt' => new \DateTime('2019-01-02'),
            'current.txt' => new \DateTime('2019-01-01'),
            'old.txt' => new \DateTime('2018-07-01'),
            'older.txt' => new \DateTime('2018-02-02'),
            'oldest.txt' => new \DateTime('2010-04-30'),
        ];

        $structure = array_fill_keys(array_keys($filesystem), '');
        vfsStream::create($structure);

        /**
         * @var string    $file
         * @var \DateTime $lastMod
         */
        foreach ($filesystem as $file => $lastMod)
        {
            $this->rootDir->getChild($file)->lastModified($lastMod->getTimestamp());
        }

        $explorer = FileExplorer::create($this->rootDir->url());
        $explorer->addMatcher(FileExplorerMatcher::modifiedAfter($cutoff));

        /** @var File $file */
        foreach ($explorer->getFileIterator() as $file)
        {
            $this->assertGreaterThan($cutoff->getTimestamp(), $file->getLastModified());
            $this->assertNotContains($file->getFilename(), 'old');
        }
    }
}
