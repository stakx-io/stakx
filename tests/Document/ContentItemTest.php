<?php declare(strict_types=1);

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
use allejo\stakx\Test\StakxTestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @internal
 *
 * @covers \allejo\stakx\Document\ContentItem
 */
class ContentItemTest extends StakxTestCase
{
    public function testContentItemFilePath(): void
    {
        $this->dummyFile->setContent(sprintf(self::FM_OBJ_TEMPLATE, '', 'Body Text'))
            ->at($this->rootDir)
        ;

        $contentItem = $this->createContentItemFromVFS();

        $this->assertEquals($this->dummyFile->url(), $contentItem->getAbsoluteFilePath());
    }

    public function testContentItemAppearsAsDraft(): void
    {
        $item = $this->createContentItem([
            'draft' => true,
        ]);
        $item->evaluateFrontMatter();

        $this->assertTrue($item->isDraft());
    }

    public function testContentItemWithEmptyFrontMatter(): void
    {
        $item = $this->createContentItemWithEmptyFrontMatter();
        $item->evaluateFrontMatter();

        $this->assertArrayHasKey('filePath', $item->getFrontMatter());
    }

    public function testContentItemWithValidFrontMatter(): void
    {
        $frontMatter = [
            'string' => 'foo',
            'bool' => false,
            'int' => 42,
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $contentItem->evaluateFrontMatter();

        foreach ($frontMatter as $key => $value) {
            $this->assertArrayHasKey($key, $contentItem->getFrontMatter());
        }
    }

    public function testContentItemFrontMatterMagicIsset(): void
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

    public function testContentItemFrontMatterDateParsing(): void
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

    public function testContentItemFrontMatterInvalidDate(): void
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
        $this->expectException(ParseException::class);

        $this->dummyFile->setContent(sprintf(self::FM_OBJ_TEMPLATE, 'invalid yaml', 'body text'))
            ->at($this->rootDir)
        ;

        return $this->createContentItemFromVFS();
    }

    public function testContentItemFrontMatterYamlVariables(): void
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

    public function testContentItemFrontMatterDynamicYamlVariables(): void
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

    public function testContentItemFrontMatterForDynamicPages(): void
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

    public function testContentItemFrontMatterForDynamicPagesWithDates(): void
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

    public function testContentItemFrontMatterArrayYamlVariables(): void
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

    public function testContentItemFrontMatterYamlVariableNotFound(): void
    {
        $frontMatter = [
            'var' => '%foobar',
        ];

        $contentItem = $this->createContentItem($frontMatter);

        try {
            $contentItem->evaluateFrontMatter();
        } catch (FileAwareException $f) {
            $this->assertInstanceOf(YamlVariableUndefinedException::class, $f->getPrevious());

            return;
        }

        $this->fail();
    }

    public function testContentItemTargetFileFromPrettyURL(): void
    {
        $frontMatter = [
            'permalink' => '/about/',
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $contentItem->evaluateFrontMatter();

        $this->assertEquals(fs::appendPath('about', 'index.html'), $contentItem->getTargetFile());
    }

    public function testContentItemTargetFileFromPrettyUrlWithRedirects(): void
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

    public function testContentItemTargetFileFromFileURL(): void
    {
        $frontMatter = [
            'permalink' => '/home/about.html',
        ];

        $contentItem = $this->createContentItem($frontMatter);
        $contentItem->evaluateFrontMatter();

        $this->assertEquals(fs::appendPath('home', 'about.html'), $contentItem->getTargetFile());
    }

    public function testContentItemTargetFileFromFileWithoutPermalinkInDir(): void
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

    public function testContentItemTargetFileFromFileWithStakxDataFolder(): void
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

    public function testContentItemWithNoFile(): void
    {
        $this->expectException(FileNotFoundException::class);

        new ContentItem(new File('foo.html.twig'));
    }

    public function testContentItemWithEmptyBodyThrowsInvalidSyntaxException(): void
    {
        $this->expectException(InvalidSyntaxException::class, 'must have a body to render');

        $this->dummyFile->setContent("---\n---\n\n  \n \t\n")
            ->at($this->rootDir)
        ;

        $this->createContentItemFromVFS();
    }

    public function testContentItemWithNoBodyThrowsInvalidSyntaxException(): void
    {
        $this->expectException(InvalidSyntaxException::class, 'Invalid FrontMatter file');

        $this->dummyFile->setContent("---\n---")
            ->at($this->rootDir)
        ;

        $this->createContentItemFromVFS();
    }

    public function testContentItemWithEmptyFileThrowsInvalidSyntaxException(): void
    {
        $this->expectException(InvalidSyntaxException::class, 'Invalid FrontMatter file');

        $file = vfsStream::newFile('foo.html.twig')->at($this->rootDir);

        new ContentItem($this->createFileObjectFromPath($file->url()));
    }

    public function testContentItemWithDeletedFileAfterCreationThrowsFileNotFoundException(): void
    {
        $this->expectException(FileNotFoundException::class);

        $content = $this->createContentItem([]);
        fs::remove($content->getAbsoluteFilePath());
        $content->readContent();
    }

    public function testContentItemWithMdExtensionFile(): void
    {
        $this->dummyFile = vfsStream::newFile('Sample Markdown.md');
        $markdownContent = file_get_contents(__DIR__ . '/../assets/EngineTemplates/Sample Markdown.md');

        $contentItem = $this->createContentItem([], $markdownContent);
        $pd = new MarkdownEngine($this->getMockAssetManager());

        $this->assertEquals($pd->parse($markdownContent, $contentItem), $contentItem->getContent());
    }

    public function testContentItemJailWithMdExtensionFile(): void
    {
        $this->dummyFile = vfsStream::newFile('Sample Markdown.md');
        $markdownContent = file_get_contents(__DIR__ . '/../assets/EngineTemplates/Sample Markdown.md');

        $contentItem = $this->createContentItemWithEmptyFrontMatter($markdownContent);
        $jailItem = $contentItem->createJail();

        $this->assertEquals($contentItem->getContent(), $jailItem->getContent());
        $this->assertEquals($jailItem->getContent(), $jailItem['content']);
    }

    public function testContentItemWithRstExtensionFile(): void
    {
        $this->dummyFile = vfsStream::newFile('Sample reStructuredText.rst');
        $rstContent = file_get_contents(__DIR__ . '/../assets/EngineTemplates/Sample reStructuredText.rst');

        $contentItem = $this->createContentItem([], $rstContent, 'document.rst');
        $pd = new RstEngine($this->getMockAssetManager());

        $this->assertEquals($pd->parse($rstContent, $contentItem), $contentItem->getContent());
    }

    public function testContentItemJailWithRstExtensionFile(): void
    {
        $this->dummyFile = vfsStream::newFile('Sample reStructuredText.rst');
        $rstContent = file_get_contents(__DIR__ . '/../assets/EngineTemplates/Sample reStructuredText.rst');

        $contentItem = $this->createContentItemWithEmptyFrontMatter($rstContent);
        $jailItem = $contentItem->createJail();

        $this->assertEquals($contentItem->getContent(), $jailItem->getContent());
        $this->assertEquals($jailItem->getContent(), $jailItem['content']);
    }

    public function testContentItemWithUnknownExtensionFile(): void
    {
        $this->dummyFile = vfsStream::newFile('Sample HTML.html');
        $htmlContent = file_get_contents(__DIR__ . '/../assets/EngineTemplates/Sample HTML.html');

        $contentItem = $this->createContentItemWithEmptyFrontMatter($htmlContent);

        $this->assertEquals($htmlContent, $contentItem->getContent());
    }

    public function testContentItemJailWithUnknownExtensionFile(): void
    {
        $this->dummyFile = vfsStream::newFile('Sample HTML.html');
        $htmlContent = file_get_contents(__DIR__ . '/../assets/EngineTemplates/Sample HTML.html');

        $contentItem = $this->createContentItemWithEmptyFrontMatter($htmlContent);
        $jailItem = $contentItem->createJail();

        $this->assertEquals($contentItem->getContent(), $jailItem->getContent());
        $this->assertEquals($jailItem->getContent(), $jailItem['content']);
    }

    public function testContentItemPermalinkSanitationMultipleForwardSlashes(): void
    {
        $multipleForwardSlashes = $this->createContentItem(['permalink' => '////index.html']);
        $multipleForwardSlashes->evaluateFrontMatter();

        $this->assertEquals('/index.html', $multipleForwardSlashes->getPermalink());
    }

    public function testContentItemPermalinkSanitationReplaceSpaces(): void
    {
        $replaceSpaces = $this->createContentItem(['permalink' => 'i like/toast and bacon.html']);
        $replaceSpaces->evaluateFrontMatter();

        $this->assertEquals('/i-like/toast-and-bacon.html', $replaceSpaces->getPermalink());
    }

    public function testContentItemPermalinkSanitationDisallowedChars(): void
    {
        $disallowedCharacters = $this->createContentItem(['permalink' => '/index-?!@#in_dex$%^&.html']);
        $disallowedCharacters->evaluateFrontMatter();

        $this->assertEquals('/index-in_dex.html', $disallowedCharacters->getPermalink());
    }

    public function testContentItemPermalinkSanitationStripExtensions(): void
    {
        $stripExtensions = $this->createContentItem(['permalink' => 'parent/foo.html.twig']);
        $stripExtensions->evaluateFrontMatter();

        $this->assertEquals('/parent/foo.html', $stripExtensions->getPermalink());
    }

    public function testContentItemPermalinkSanitationSpecialDotSlash(): void
    {
        $specialDotSlash = $this->createContentItem(['permalink' => './index.html']);
        $specialDotSlash->evaluateFrontMatter();

        $this->assertEquals('/index.html', $specialDotSlash->getPermalink());
    }

    public function testContentItemPermalinkSanitationUpperCaseToLower(): void
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
     */
    private function createContentItemFromVFS(): ContentItem
    {
        return new ContentItem(
            $this->createFileObjectFromPath($this->dummyFile->url())
        );
    }

    /**
     * Create a virtual ContentItem with only body text.
     *
     * @param string $body
     */
    private function createContentItemWithEmptyFrontMatter($body = 'Body Text'): ContentItem
    {
        return $this->createContentItem([], $body);
    }

    /**
     * Create a virtual ContentItem.
     *
     * @param array  $frontMatter
     * @param string $body
     * @param string $filename
     */
    private function createContentItem($frontMatter, $body = 'Body Text', $filename = 'document.md'): ContentItem
    {
        /** @var ContentItem $contentItem */
        $contentItem = $this->createFrontMatterDocumentOfType(ContentItem::class, $filename, $frontMatter, $body);
        $contentItem->setMarkupEngine($this->getMockMarkupEngineManager());

        return $contentItem;
    }
}
