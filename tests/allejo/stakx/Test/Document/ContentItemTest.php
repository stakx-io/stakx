<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Document;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Exception\FileAwareException;
use allejo\stakx\Exception\InvalidSyntaxException;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\FrontMatter\Exception\YamlVariableUndefinedException;
use allejo\stakx\MarkupEngine\MarkdownEngine;
use allejo\stakx\MarkupEngine\RstEngine;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use bovigo\vfs\vfsStream;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;

class ContentItemTests extends PHPUnit_Stakx_TestCase
{
    public function testContentItemFilePath()
    {
        $this->dummyFile->setContent(sprintf(self::FM_OBJ_TEMPLATE, '', 'Body Text'))
                        ->at($this->rootDir)
        ;

        $contentItem = $this->createContentItemFromVFS();

        $this->assertEquals($this->dummyFile->url(), $contentItem->getAbsoluteFilePath());
    }

    public function testContentItemAppearsAsDraft()
    {
        $item = $this->createContentItem([
            'draft' => true,
        ]);
        $item->evaluateFrontMatter();

        $this->assertTrue($item->isDraft());
    }

    public function testContentItemWithEmptyFrontMatter()
    {
        $item = $this->createContentItemWithEmptyFrontMatter();
        $item->evaluateFrontMatter();

        $this->assertArrayHasKey('filePath', $item->getFrontMatter());
    }

    public function testContentItemWithValidFrontMatter()
    {
        $frontMatter = [
            'string' => 'foo',
            'bool' => false,
            'int' => 42,
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $contentItem->evaluateFrontMatter();

        foreach ($frontMatter as $key => $value)
        {
            $this->assertArrayHasKey($key, $contentItem->getFrontMatter());
        }
    }

    public function testContentItemFrontMatterMagicIsset()
    {
        $frontMatter = [
            'foo' => 1,
            'bar' => 2,
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $contentItem->evaluateFrontMatter();

        $this->assertTrue(isset($contentItem['foo']));
        $this->assertTrue(isset($contentItem['bar']));
    }

    public function testContentItemFrontMatterDateParsing()
    {
        $year = '2000';
        $month = '12';
        $day = '01';

        $frontMatter = [
            'date' => sprintf('%s-%s-%s', $year, $month, $day),
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $contentItem->evaluateFrontMatter();

        $this->assertEquals($year, $contentItem['year']);
        $this->assertEquals($month, $contentItem['month']);
        $this->assertEquals($day, $contentItem['day']);
    }

    public function testContentItemFrontMatterInvalidDate()
    {
        $frontMatter = [
            'date' => 'foo bar',
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $contentItem->evaluateFrontMatter();

        $this->assertNull($contentItem['year']);
        $this->assertNull($contentItem['month']);
        $this->assertNull($contentItem['day']);
    }

    public function testContentItemFrontMatterInvalidYaml()
    {
        $this->setExpectedException(ParseException::class);

        $this->dummyFile->setContent(sprintf(self::FM_OBJ_TEMPLATE, 'invalid yaml', 'body text'))
             ->at($this->rootDir)
        ;

        return $this->createContentItemFromVFS();
    }

    public function testContentItemFrontMatterYamlVariables()
    {
        $fname = 'jane';
        $lname = 'doe';
        $frontMatter = [
            'fname' => $fname,
            'lname' => $lname,
            'name' => '%fname %lname',
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $contentItem->evaluateFrontMatter();
        $finalFM = $contentItem->getFrontMatter();

        $this->assertEquals(sprintf('%s %s', $fname, $lname), $finalFM['name']);
    }

    public function testContentItemFrontMatterDynamicYamlVariables()
    {
        $fname = 'John';
        $lname = 'Doe';
        $suffix = 'Jr';
        $frontMatter = [
            'fname' => $fname,
            'lname' => $lname,
            'suffix' => $suffix,
            'name' => '%fname %lname',
            'name_full' => '%name %suffix',
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $contentItem->evaluateFrontMatter();
        $finalFront = $contentItem->getFrontMatter();

        $this->assertEquals(sprintf('%s %s', $finalFront['name'], $suffix), $finalFront['name_full']);
    }

    public function testContentItemFrontMatterForDynamicPages()
    {
        $frontMatter = [
            'permalink' => '/blog/%title',
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $individualFrontMatter = [
            'title' => 'Hello World',
        ];

        $contentItem->evaluateFrontMatter($individualFrontMatter);

        $this->assertEquals('/blog/hello-world', $contentItem->getPermalink());
    }

    public function testContentItemFrontMatterForDynamicPagesWithDates()
    {
        $frontMatter = [
            'permalink' => '/blog/%year/%month/%day/%title',
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $individualFrontMatter = [
            'title' => 'Hello World',
            'date' => '2016-01-01',
        ];

        $contentItem->evaluateFrontMatter($individualFrontMatter);

        $this->assertEquals('/blog/2016/01/01/hello-world', $contentItem->getPermalink());
    }

    public function testContentItemFrontMatterArrayYamlVariables()
    {
        $fname = 'John';
        $lname = 'Doe';
        $frontMatter = [
            'fname' => $fname,
            'lname' => $lname,
            'other' => [
                'name_l' => '%lname, %fname',
                'name_d' => '%fname %fname',
            ],
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $contentItem->evaluateFrontMatter();
        $finalFront = $contentItem->getFrontMatter();

        $this->assertEquals(sprintf('%s, %s', $lname, $fname), $finalFront['other']['name_l']);
        $this->assertEquals(sprintf('%s %s', $fname, $fname), $finalFront['other']['name_d']);
    }

    public function testContentItemFrontMatterYamlVariableNotFound()
    {
        $frontMatter = [
            'var' => '%foobar',
        ];

        $contentItem = $this->createContentItem($frontMatter);

        try
        {
            $contentItem->evaluateFrontMatter();
        }
        catch (FileAwareException $f)
        {
            $this->assertInstanceOf(YamlVariableUndefinedException::class, $f->getPrevious());

            return;
        }

        $this->fail();
    }

    public function testContentItemTargetFileFromPrettyURL()
    {
        $frontMatter = [
            'permalink' => '/about/',
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $contentItem->evaluateFrontMatter();

        $this->assertEquals(fs::appendPath('about', 'index.html'), $contentItem->getTargetFile());
    }

    public function testContentItemTargetFileFromPrettyUrlWithRedirects()
    {
        $frontMatter = [
            'permalink' => [
                '/canonical/',
                '/redirect/',
                '/redirect-me/',
                '/redirect-me-also/',
            ],
        ];
        $contentItem = $this->createContentItem($frontMatter);
        $contentItem->evaluateFrontMatter();

        $this->assertEquals(fs::appendPath('canonical', 'index.html'), $contentItem->getTargetFile());
        $this->assertEquals('/canonical/', $contentItem->getPermalink());
        $this->assertContains('/redirect/', $contentItem->getRedirects());
        $this->assertContains('/redirect-me/', $contentItem->getRedirects());
    }

    public function testContentItemTargetFileFromFileURL()
    {
        $frontMatter = [
            'permalink' => '/home/about.html',
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $contentItem->evaluateFrontMatter();

        $this->assertEquals(fs::appendPath('home', 'about.html'), $contentItem->getTargetFile());
    }

    public function testContentItemTargetFileFromFileWithoutPermalinkInDir()
    {
        $root = vfsStream::create([
            'dir' => [
                'foo.html.twig' => sprintf(self::FM_OBJ_TEMPLATE, '', 'Body Text'),
            ],
        ]);
        $url = $root->getChild('dir/foo.html.twig')->url();

        $contentItem = new ContentItem($this->createFileObjectFromPath($url));
        $contentItem->evaluateFrontMatter();

        $this->assertEquals(fs::appendPath('root', 'dir', 'foo.html'), $contentItem->getTargetFile());
    }

    public function testContentItemTargetFileFromFileWithStakxDataFolder()
    {
        $rootDir = vfsStream::setup('_bacon');
        $file = vfsStream::newFile('foo.html.twig');

        $file->setContent(sprintf(self::FM_OBJ_TEMPLATE, '', 'Body Text'))
             ->at($rootDir)
        ;

        $url = $rootDir->getChild('foo.html.twig')->url();

        $contentItem = new ContentItem($this->createFileObjectFromPath($url));
        $contentItem->evaluateFrontMatter();

        $this->assertEquals('foo.html', $contentItem->getTargetFile());
    }

    public function testContentItemWithNoFile()
    {
        $this->setExpectedException(FileNotFoundException::class);

        new ContentItem(new File('foo.html.twig'));
    }

    public function testContentItemWithEmptyBodyThrowsInvalidSyntaxException()
    {
        $this->setExpectedException(InvalidSyntaxException::class, 'must have a body to render');

        $this->dummyFile->setContent("---\n---\n\n  \n \t\n")
                        ->at($this->rootDir)
        ;

        $this->createContentItemFromVFS();
    }

    public function testContentItemWithNoBodyThrowsInvalidSyntaxException()
    {
        $this->setExpectedException(InvalidSyntaxException::class, 'Invalid FrontMatter file');

        $this->dummyFile->setContent("---\n---")
                        ->at($this->rootDir)
        ;

        $this->createContentItemFromVFS();
    }

    public function testContentItemWithEmptyFileThrowsInvalidSyntaxException()
    {
        $this->setExpectedException(InvalidSyntaxException::class, 'Invalid FrontMatter file');

        $file = vfsStream::newFile('foo.html.twig')->at($this->rootDir);

        new ContentItem($this->createFileObjectFromPath($file->url()));
    }

    public function testContentItemWithDeletedFileAfterCreationThrowsFileNotFoundException()
    {
        $this->setExpectedException(FileNotFoundException::class);

        $content = $this->createContentItem([]);
        fs::remove($content->getAbsoluteFilePath());
        $content->readContent();
    }

    public function testContentItemWithMdExtensionFile()
    {
        $this->dummyFile = vfsStream::newFile('Sample Markdown.md');
        $markdownContent = file_get_contents(__DIR__ . '/../assets/EngineTemplates/Sample Markdown.md');

        $contentItem = $this->createContentItem([], $markdownContent);
        $pd = new MarkdownEngine($this->getMockAssetManager());

        $this->assertEquals($pd->parse($markdownContent, $contentItem), $contentItem->getContent());
    }

    public function testContentItemJailWithMdExtensionFile()
    {
        $this->dummyFile = vfsStream::newFile('Sample Markdown.md');
        $markdownContent = file_get_contents(__DIR__ . '/../assets/EngineTemplates/Sample Markdown.md');

        $contentItem = $this->createContentItemWithEmptyFrontMatter($markdownContent);
        $jailItem = $contentItem->createJail();

        $this->assertEquals($contentItem->getContent(), $jailItem->getContent());
        $this->assertEquals($jailItem->getContent(), $jailItem['content']);
    }

    public function testContentItemWithRstExtensionFile()
    {
        $this->dummyFile = vfsStream::newFile('Sample reStructuredText.rst');
        $rstContent = file_get_contents(__DIR__ . '/../assets/EngineTemplates/Sample reStructuredText.rst');

        $contentItem = $this->createContentItem([], $rstContent, 'document.rst');
        $pd = new RstEngine($this->getMockAssetManager());

        $this->assertEquals((string)$pd->parse($rstContent, $contentItem), $contentItem->getContent());
    }

    public function testContentItemJailWithRstExtensionFile()
    {
        $this->dummyFile = vfsStream::newFile('Sample reStructuredText.rst');
        $rstContent = file_get_contents(__DIR__ . '/../assets/EngineTemplates/Sample reStructuredText.rst');

        $contentItem = $this->createContentItemWithEmptyFrontMatter($rstContent);
        $jailItem = $contentItem->createJail();

        $this->assertEquals($contentItem->getContent(), $jailItem->getContent());
        $this->assertEquals($jailItem->getContent(), $jailItem['content']);
    }

    public function testContentItemWithUnknownExtensionFile()
    {
        $this->dummyFile = vfsStream::newFile('Sample HTML.html');
        $htmlContent = file_get_contents(__DIR__ . '/../assets/EngineTemplates/Sample HTML.html');

        $contentItem = $this->createContentItemWithEmptyFrontMatter($htmlContent);

        $this->assertEquals($htmlContent, $contentItem->getContent());
    }

    public function testContentItemJailWithUnknownExtensionFile()
    {
        $this->dummyFile = vfsStream::newFile('Sample HTML.html');
        $htmlContent = file_get_contents(__DIR__ . '/../assets/EngineTemplates/Sample HTML.html');

        $contentItem = $this->createContentItemWithEmptyFrontMatter($htmlContent);
        $jailItem = $contentItem->createJail();

        $this->assertEquals($contentItem->getContent(), $jailItem->getContent());
        $this->assertEquals($jailItem->getContent(), $jailItem['content']);
    }

    public function testContentItemPermalinkSanitationMultipleForwardSlashes()
    {
        $multipleForwardSlashes = $this->createContentItem(['permalink' => '////index.html']);
        $multipleForwardSlashes->evaluateFrontMatter();

        $this->assertEquals('/index.html', $multipleForwardSlashes->getPermalink());
    }

    public function testContentItemPermalinkSanitationReplaceSpaces()
    {
        $replaceSpaces = $this->createContentItem(['permalink' => 'i like/toast and bacon.html']);
        $replaceSpaces->evaluateFrontMatter();

        $this->assertEquals('/i-like/toast-and-bacon.html', $replaceSpaces->getPermalink());
    }

    public function testContentItemPermalinkSanitationDisallowedChars()
    {
        $disallowedCharacters = $this->createContentItem(['permalink' => '/index-?!@#in_dex$%^&.html']);
        $disallowedCharacters->evaluateFrontMatter();

        $this->assertEquals('/index-in_dex.html', $disallowedCharacters->getPermalink());
    }

    public function testContentItemPermalinkSanitationStripExtensions()
    {
        $stripExtensions = $this->createContentItem(['permalink' => 'parent/foo.html.twig']);
        $stripExtensions->evaluateFrontMatter();

        $this->assertEquals('/parent/foo.html', $stripExtensions->getPermalink());
    }

    public function testContentItemPermalinkSanitationSpecialDotSlash()
    {
        $specialDotSlash = $this->createContentItem(['permalink' => './index.html']);
        $specialDotSlash->evaluateFrontMatter();

        $this->assertEquals('/index.html', $specialDotSlash->getPermalink());
    }

    public function testContentItemPermalinkSanitationUpperCaseToLower()
    {
        $uppercase = $this->createContentItem(['permalink' => 'UPPER_CASE.html']);
        $uppercase->evaluateFrontMatter();

        $this->assertEquals('/upper_case.html', $uppercase->getPermalink());
    }

    //
    // Utilities
    //

    /**
     * Create a ContentItem from the default virtual file in the VFS.
     *
     * @return ContentItem
     */
    private function createContentItemFromVFS()
    {
        return new ContentItem(
            $this->createFileObjectFromPath($this->dummyFile->url())
        );
    }

    /**
     * Create a virtual ContentItem with only body text.
     *
     * @param string $body
     *
     * @return ContentItem
     */
    private function createContentItemWithEmptyFrontMatter($body = 'Body Text')
    {
        return $this->createContentItem([], $body);
    }

    /**
     * Create a virtual ContentItem.
     *
     * @param array  $frontMatter
     * @param string $body
     * @param string $filename
     *
     * @return ContentItem
     */
    private function createContentItem($frontMatter, $body = 'Body Text', $filename = 'document.md')
    {
        /** @var ContentItem $contentItem */
        $contentItem = $this->createFrontMatterDocumentOfType(ContentItem::class, $filename, $frontMatter, $body);
        $contentItem->setMarkupEngine($this->getMockMarkupEngineManager());

        return $contentItem;
    }
}
