<?php

namespace allejo\stakx\tests;

use allejo\stakx\Engines\MarkdownEngine;
use allejo\stakx\Engines\RstEngine;
use allejo\stakx\Exception\YamlVariableUndefinedException;
use allejo\stakx\Object\ContentItem;
use allejo\stakx\System\Filesystem;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ContentItemTests extends PHPUnit_Framework_TestCase
{
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

    /**
     * @var Filesystem
     */
    protected $fs;

    public function setUp ()
    {
        $this->fileTemplate = "---\n%s\n---\n\n%s";
        $this->dummyFile    = vfsStream::newFile('foo.html.twig');
        $this->rootDir      = vfsStream::setup();
        $this->fs           = new Filesystem();
    }

    public function testContentItemFilePath ()
    {
        $this->dummyFile->setContent(sprintf($this->fileTemplate, "", "Body Text"))
                        ->at($this->rootDir);

        $contentItem = new ContentItem($this->dummyFile->url());

        $this->assertEquals($this->dummyFile->url(), $contentItem->getFilePath());
    }

    public function testContentItemWithEmptyFrontMatter ()
    {
        $item = $this->createValidFileWithEmptyFrontMatter();

        $this->assertEmpty($item->getFrontMatter());
    }

    public function testContentItemWitValidFrontMatter ()
    {
        $frontMatter = array(
            "string" => "foo",
            "bool"   => false,
            "int"    => 42
        );

        $contentItem = $this->createSampleValidFile($frontMatter);

        $this->assertEquals($frontMatter, $contentItem->getFrontMatter());
    }

    public function testContentItemFrontMatterMagicIsset ()
    {
        $frontMatter = array(
            'foo' => 1,
            'bar' => 2
        );

        $contentItem = $this->createSampleValidFile($frontMatter);

        $this->assertTrue(isset($contentItem->foo));
        $this->assertTrue(isset($contentItem->bar));
    }

    public function testContentItemFrontMatterDateParsing ()
    {
        $year  = "2000";
        $month = "12";
        $day   = "01";

        $frontMatter = array(
            "date" => sprintf("%s-%s-%s", $year, $month, $day)
        );

        $this->createSampleValidFile($frontMatter);

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

        $contentItem = $this->createSampleValidFile($frontMatter);

        $this->assertNull($contentItem->year);
        $this->assertNull($contentItem->month);
        $this->assertNull($contentItem->day);
    }

    public function testContentItemFrontMatterInvalidYaml ()
    {
        $this->setExpectedException(ParseException::class);

        $this->dummyFile->setContent(sprintf($this->fileTemplate, 'invalid yaml', 'body text'))
             ->at($this->rootDir);

        return (new ContentItem($this->dummyFile->url()));
    }

    public function testContentItemFrontMatterYamlVariables ()
    {
        $fname = "jane";
        $lname = "doe";
        $frontMatter = array(
            "fname" => $fname,
            "lname" => $lname,
            "name"  => "%fname %lname"
        );

        $contentItem = $this->createSampleValidFile($frontMatter);
        $finalFM = $contentItem->getFrontMatter();

        $this->assertEquals(sprintf("%s %s", $fname, $lname), $finalFM['name']);
    }

    public function testContentItemFrontMatterDynamicYamlVariables ()
    {
        $fname  = "John";
        $lname  = "Doe";
        $suffix = "Jr";
        $frontMatter = array(
            "fname"  => $fname,
            "lname"  => $lname,
            "suffix" => $suffix,
            "name"   => "%fname %lname",
            "name_full" => "%name %suffix"
        );

        $contentItem = $this->createSampleValidFile($frontMatter);
        $finalFront = $contentItem->getFrontMatter();

        $this->assertEquals(sprintf("%s %s", $finalFront['name'], $suffix), $finalFront['name_full']);
    }

    public function testContentItemFrontMatterForDynamicPages ()
    {
        $frontMatter = array(
            'permalink'  => '/blog/%title'
        );

        $contentItem = $this->createSampleValidFile($frontMatter);
        $individualFrontMatter = array(
            'title' => 'Hello World'
        );

        $contentItem->evaluateFrontMatter($individualFrontMatter);

        $this->assertEquals('/blog/hello-world', $contentItem->getPermalink());
    }

    public function testContentItemFrontMatterForDynamicPagesWithDates ()
    {
        $frontMatter = array(
            'permalink'  => '/blog/%year/%month/%day/%title'
        );

        $contentItem = $this->createSampleValidFile($frontMatter);
        $individualFrontMatter = array(
            'title' => 'Hello World',
            'date'  => '2016-01-01'
        );

        $contentItem->evaluateFrontMatter($individualFrontMatter);

        $this->assertEquals('/blog/2016/01/01/hello-world', $contentItem->getPermalink());
    }

    public function testContentItemFrontMatterArrayYamlVariables ()
    {
        $fname  = "John";
        $lname  = "Doe";
        $frontMatter = array(
            "fname" => $fname,
            "lname" => $lname,
            "other" => array(
                "name_l" => "%lname, %fname",
                "name_d" => "%fname %fname"
            )
        );

        $contentItem = $this->createSampleValidFile($frontMatter);
        $finalFront = $contentItem->getFrontMatter();

        $this->assertEquals(sprintf("%s, %s", $lname, $fname), $finalFront['other']['name_l']);
        $this->assertEquals(sprintf("%s %s", $fname, $fname), $finalFront['other']['name_d']);
    }

    public function testContentItemFrontMatterYamlVariableNotFound ()
    {
        $this->setExpectedException(YamlVariableUndefinedException::class);

        $frontMatter = array(
            "var"   => "%foobar"
        );

        $contentItem = $this->createSampleValidFile($frontMatter);
        $contentItem->getFrontMatter();
    }

    public function testContentItemTargetFileFromPrettyURL ()
    {
        $frontMatter = array(
            "permalink" => "/about/"
        );

        $contentItem = $this->createSampleValidFile($frontMatter);

        $this->assertEquals($this->fs->appendPath('about', 'index.html'), $contentItem->getTargetFile());
    }

    public function testContentItemTargetFileFromPrettyUrlWithRedirects ()
    {
        $frontMatter = array(
            "permalink" => array(
                "/canonical/",
                "/redirect/",
                "/redirect-me/",
                "/redirect-me-also/"
            )
        );
        $contentItem = $this->createSampleValidFile($frontMatter);

        $this->assertEquals($this->fs->appendPath('canonical', 'index.html'), $contentItem->getTargetFile());
        $this->assertEquals("/canonical/", $contentItem->getPermalink());
        $this->assertContains("/redirect/", $contentItem->getRedirects());
        $this->assertContains("/redirect-me/", $contentItem->getRedirects());
    }

    public function testContentItemTargetFileFromFileURL ()
    {
        $frontMatter = array(
            "permalink" => "/home/about.html"
        );

        $contentItem = $this->createSampleValidFile($frontMatter);

        $this->assertEquals($this->fs->appendPath('home', 'about.html'), $contentItem->getTargetFile());
    }

    public function testContentItemTargetFileFromFileWithoutPermalinkAtRoot ()
    {
        $contentItem = $this->createValidFileWithEmptyFrontMatter();

        $this->assertEquals($this->fs->appendPath('root', 'foo.html'), $contentItem->getTargetFile());
    }

    public function testContentItemTargetFileFromFileWithoutPermalinkInDir ()
    {
        $root = vfsStream::create(array(
            'dir' => array (
                'foo.html.twig' => sprintf($this->fileTemplate, "", "Body Text")
            )
        ));

        $contentItem = new ContentItem($root->getChild('dir/foo.html.twig')->url());

        $this->assertEquals($this->fs->appendPath('root', 'dir', 'foo.html'), $contentItem->getTargetFile());
    }

    public function testContentItemTargetFileFromFileWithStakxDataFolder ()
    {
        $rootDir = vfsStream::setup('_bacon');
        $file    = vfsStream::newFile("foo.html.twig");

        $file->setContent(sprintf($this->fileTemplate, "", "Body Text"))
             ->at($rootDir);

        $contentItem = new ContentItem($rootDir->getChild('foo.html.twig')->url());

        $this->assertEquals('foo.html', $contentItem->getTargetFile());
    }

    public function testContentItemWithNoFile ()
    {
        $this->setExpectedException(FileNotFoundException::class);

        new ContentItem('foo.html.twig');
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

    public function testContentItemWithMdExtensionFile ()
    {
        $this->dummyFile = vfsStream::newFile('Sample Markdown.md');
        $markdownContent = file_get_contents(__DIR__ . '/assets/Sample Markdown.md');

        $contentItem = $this->createValidFileWithEmptyFrontMatter($markdownContent);
        $pd = new MarkdownEngine();

        $this->assertEquals($pd->parse($markdownContent), $contentItem->getContent());
    }

    public function testContentItemWithRstExtensionFile ()
    {
        $this->dummyFile = vfsStream::newFile('Sample reStructuredText.rst');
        $rstContent = file_get_contents(__DIR__ . '/assets/Sample reStructuredText.rst');

        $contentItem = $this->createValidFileWithEmptyFrontMatter($rstContent);
        $pd = new RstEngine();

        $this->assertEquals((string)$pd->parse($rstContent), $contentItem->getContent());
    }

    public function testContentItemWithUnknownExtensionFile ()
    {
        $this->dummyFile = vfsStream::newFile('Sample HTML.html');
        $htmlContent = file_get_contents(__DIR__ . '/assets/Sample HTML.html');

        $contentItem = $this->createValidFileWithEmptyFrontMatter($htmlContent);

        $this->assertEquals($htmlContent, $contentItem->getContent());
    }

    private function createValidFileWithEmptyFrontMatter ($body = "Body Text")
    {
        $this->dummyFile->setContent(sprintf($this->fileTemplate, "", $body))
                        ->at($this->rootDir);

        return (new ContentItem($this->dummyFile->url()));
    }

    private function createSampleValidFile ($frontMatter, $body = "Body Text")
    {
        $this->dummyFile->setContent(sprintf($this->fileTemplate, Yaml::dump($frontMatter, 2), $body))
                        ->at($this->rootDir);

        return (new ContentItem($this->dummyFile->url()));
    }
}