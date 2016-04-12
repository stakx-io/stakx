<?php

namespace allejo\stakx\tests;

use allejo\stakx\Object\ContentItem;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Yaml\Yaml;

class ContentItemTests extends PHPUnit_Framework_TestCase
{
    const TargetClass = '\allejo\stakx\Object\ContentItem';

    /**
     * @var string
     */
    protected $fileTemplate;

    /**
     * @var vfsStreamFile
     */
    protected $dummyFile;

    /**
     * @var vfsStreamDirectory
     */
    protected $rootDir;

    public function setUp ()
    {
        $this->fileTemplate = "---\n%s\n---\n\n%s";
        $this->dummyFile    = vfsStream::newFile('foo.html.twig');
        $this->rootDir      = vfsStream::setup();
    }

    public function testContentItemWithEmptyFrontMatter ()
    {
        $this->dummyFile->setContent(sprintf($this->fileTemplate, "", "ContentItem Body"))
                        ->at($this->rootDir);

        $item = new ContentItem($this->dummyFile->url());

        $this->assertEmpty($item->getFrontMatter());
    }

    public function testContentItemFrontMatterIsCorrect ()
    {
        $frontMatter = array(
            "string" => "foo",
            "bool"   => false,
            "int"    => 42
        );

        $this->dummyFile->setContent(sprintf($this->fileTemplate, Yaml::dump($frontMatter, 2), "Body Text"))
                        ->at($this->rootDir);

        $contentItem = new ContentItem($this->dummyFile->url());

        $this->assertEquals($frontMatter, $contentItem->getFrontMatter());
    }

    public function testContentItemFrontMatterDateParsing ()
    {
        $year  = "2000";
        $month = "12";
        $day   = "01";

        $frontMatter = array(
            "date" => sprintf("%s-%s-%s", $year, $month, $day)
        );

        $this->dummyFile->setContent(sprintf($this->fileTemplate, Yaml::dump($frontMatter, 2), "Body Text"))
                        ->at($this->rootDir);

        $contentItem = new ContentItem($this->dummyFile->url());

        $this->assertEquals($year,  $contentItem->year);
        $this->assertEquals($month, $contentItem->month);
        $this->assertEquals($day,   $contentItem->day);
    }

    public function testContentItemFrontMatterInvalidDate ()
    {
        $frontMatter = array(
            "date" => "foo bar"
        );

        $this->dummyFile->setContent(sprintf($this->fileTemplate, Yaml::dump($frontMatter, 2), "Body Text"))
                        ->at($this->rootDir);

        $contentItem = new ContentItem($this->dummyFile->url());

        $this->assertNull($contentItem->year);
        $this->assertNull($contentItem->month);
        $this->assertNull($contentItem->day);
    }

    public function testContentItemWithNoBodyThrowsIOException ()
    {
        $this->setExpectedException(IOException::class);

        $this->dummyFile->setContent("---\n---")
                        ->at($this->rootDir);

        new ContentItem($this->dummyFile->url());
    }

    public function testContentItemWithEmptyFileThrowsIOException ()
    {
        $this->setExpectedException(IOException::class);

        $file = vfsStream::newFile('foo.html.twig')->at($this->rootDir);

        new ContentItem($file->url());
    }
}