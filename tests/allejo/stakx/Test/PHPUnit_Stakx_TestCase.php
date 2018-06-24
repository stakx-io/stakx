<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test;

use allejo\stakx\Command\BuildCommand;
use allejo\stakx\Configuration;
use allejo\stakx\Logger;
use allejo\stakx\Document\FrontMatterDocument;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\Filesystem;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Filesystem\Folder;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\Manager\MenuManager;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\MarkupEngine\MarkdownEngine;
use allejo\stakx\MarkupEngine\MarkupEngineManager;
use allejo\stakx\MarkupEngine\PlainTextEngine;
use allejo\stakx\MarkupEngine\RstEngine;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use allejo\stakx\Templating\Twig\TwigExtension;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Yaml;

abstract class PHPUnit_Stakx_TestCase extends \PHPUnit_Framework_TestCase
{
    const FM_OBJ_TEMPLATE = "---\n%s\n---\n\n%s";

    /** @var string */
    protected $assetFolder;
    /** @var vfsStreamFile */
    protected $dummyFile;
    /** @var vfsStreamDirectory */
    protected $rootDir;

    public function setUp()
    {
        $this->dummyFile = vfsStream::newFile('stakx.html.twig');
        $this->rootDir = vfsStream::setup();

        Service::resetRuntimeFlags();

        Service::setWorkingDirectory(null);
        Service::setRuntimeFlag(RuntimeStatus::USING_HIGHLIGHTER);

        // Inspect the VFS as an array
        // vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure();
    }

    public function tearDown()
    {
        if ($this->assetFolder !== null)
        {
            fs::remove($this->assetFolder);
        }
    }

    ///
    // Assertion Functions
    ///

    /**
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    protected function assertStringContains($needle, $haystack, $message = '')
    {
        $this->assertNotFalse(strpos($haystack, $needle), $message);
    }

    /**
     * @param string $fileContent
     * @param string $filePath
     * @param string $message
     */
    protected function assertFileContains($fileContent, $filePath, $message = '')
    {
        (substr($filePath, -1, 1) == '/') && $filePath .= 'index.html';

        $contents = file_get_contents($filePath);

        $this->assertStringContains($fileContent, $contents, $message);
    }

    ///
    // Filesystem Functions
    ///

    /**
     * Create a temporary folder where temporary file writes will be made to.
     *
     * @param string $folderName
     */
    protected function createAssetFolder($folderName)
    {
        $this->assetFolder = fs::getRelativePath(fs::appendPath(__DIR__, $folderName));

        fs::mkdir($this->assetFolder);
    }

    /**
     * Write a file to the asset folder.
     *
     * This file will be written to the actual filesystem and not the virtual filesystem. This file will be deleted at
     * each tearDown().
     *
     * @param string $fileName
     * @param string $content
     *
     * @return string Path to the temporary file; relative to the project's root
     */
    protected function createPhysicalFile($fileName, $content)
    {
        $folder = new Folder($this->assetFolder);
        $folder->writeFile($fileName, $content);

        return fs::appendPath($this->assetFolder, $fileName);
    }

    /**
     * Write a file to the virtual filesystem.
     *
     * This file will be deleted at each tearDown().
     *
     * @param string $filename
     * @param string $content
     *
     * @return string the URL of the file on the virtual filesystem
     */
    protected function createVirtualFile($filename, $content)
    {
        $file = vfsStream::newFile($filename);
        $file
            ->setContent($content)
            ->at($this->rootDir)
        ;

        return $file->url();
    }

    /**
     * Create an object of a given type.
     *
     * This will create a virtual file and then create an object of the specified type for the created file.
     *
     * @param string $classType
     * @param string $filename
     * @param string $content
     *
     * @return object An instance of $classType
     */
    protected function createDocumentOfType($classType, $filename, $content)
    {
        $file = $this->createVirtualFile($filename, $content);

        return new $classType(new File($file));
    }

    /**
     * Create an object of a given type following the Front Matter format.
     *
     * @param string $classType
     * @param string $filename
     * @param array  $frontMatter
     * @param string $content
     *
     * @return object An instance of $classType
     */
    protected function createFrontMatterDocumentOfType($classType, $filename = null, $frontMatter = [], $content = 'Body Text')
    {
        $body = $this->buildFrontMatterTemplate($frontMatter, $content);

        if (!$filename)
        {
            $filename = hash('sha256', uniqid(mt_rand(), true), false);
        }

        return $this->createDocumentOfType($classType, $filename, $body);
    }

    /**
     * Create multiple virtual files from a given array of information.
     *
     * ```php
     * $elements = [
     *     [
     *         'filename' => '<string>',
     *         'frontmatter' => [],
     *         'body' => '<string>',
     *     ],
     * ];
     * ```
     *
     * @param string $classType
     * @param array  $elements
     *
     * @return array
     */
    protected function createMultipleFrontMatterDocumentsOfType($classType, $elements)
    {
        $results = [];

        foreach ($elements as $element)
        {
            $filename = (isset($element['filename'])) ? $element['filename'] : null;
            $frontMatter = (!isset($element['frontmatter']) || empty($element['frontmatter'])) ? [] : $element['frontmatter'];
            $body = (isset($element['body'])) ? $element['body'] : 'Body Text';

            /** @var FrontMatterDocument $item */
            $item = $this->createFrontMatterDocumentOfType($classType, $filename, $frontMatter, $body);
            $item->evaluateFrontMatter();

            $results[] = $item;
        }

        return $results;
    }

    /**
     * Create a File object from a given path.
     *
     * @deprecated
     *
     * @param string $filePath
     *
     * @return File
     */
    protected function createFileObjectFromPath($filePath)
    {
        return new File($filePath);
    }

    ///
    // Mock Objects
    ///

    /**
     * @return Configuration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockConfiguration()
    {
        $stub = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $stub->method('getConfiguration')->willReturn([]);
        $stub->method('getTwigAutoescape')->willReturn(false);

        return $stub;
    }

    /**
     * @return PageManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockPageManager()
    {
        $stub = $this->getMockBuilder(PageManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $stub->method('getJailedStaticPageViews')->willReturn([]);

        return $stub;
    }

    /**
     * @return MenuManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockMenuManager()
    {
        $stub = $this->getMockBuilder(MenuManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $stub->method('getSiteMenu')->willReturn([]);

        return $stub;
    }

    /**
     * @return CollectionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockCollectionManager()
    {
        $stub = $this->getMockBuilder(CollectionManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $stub->method('getJailedCollections')->willReturn([]);

        return $stub;
    }

    /**
     * @return DataManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockDataManager()
    {
        $stub = $this->getMockBuilder(DataManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $stub->method('getJailedDataItems')->willReturn([]);

        return $stub;
    }

    /**
     * @return TwigExtension
     */
    protected function getMockTwigExtension()
    {
        // too lazy to actually mock all the methods... just create an actual instance of and dub it a "mock" to match
        // all the other mock methods. if this causes problems: sorry, future me!
        return new TwigExtension($this->getMockMarkupEngineManager());
    }

    /**
     * @return MarkupEngineManager
     */
    protected function getMockMarkupEngineManager()
    {
        // Just trying to keep the naming convention the same, but create an actual markup engine manager since it's
        // necessary in a lot of the unit tests
        $markupEngine = new MarkupEngineManager();

        $markupEngine->addMarkupEngines([
            new MarkdownEngine(),
            new RstEngine(),
            new PlainTextEngine(),
        ]);

        return $markupEngine;
    }

    /**
     * Get a mock EventDispatcher.
     *
     * @return EventDispatcherInterface
     */
    protected function getMockEventDistpatcher()
    {
        return $this->getMock(EventDispatcherInterface::class);
    }

    /**
     * Get a mock logger.
     *
     * @return LoggerInterface
     */
    protected function getMockLogger()
    {
        return $this->getMock(LoggerInterface::class);
    }

    /**
     * Get a real logger instance that will save output to the console.
     *
     * @return Logger
     */
    protected function getReadableLogger()
    {
        stream_filter_register('intercept', StreamInterceptor::class);
        $stakxLogger = new Logger(new ConsoleOutput());
        stream_filter_append($stakxLogger->getOutputInterface()->getStream(), 'intercept');

        return $stakxLogger;
    }

    ///
    // Utility Functions
    ///

    /**
     * Get the directory of the unit tests.
     *
     * @return string
     */
    protected static function getTestRoot()
    {
        return __DIR__;
    }

    /**
     * Generate a FrontMatter-ready syntax to be used as a file's content.
     *
     * @param array  $frontMatter
     * @param string $body
     *
     * @return string
     */
    protected function buildFrontMatterTemplate(array $frontMatter = [], $body = 'Body text')
    {
        $fm = (empty($frontMatter)) ? '' : Yaml::dump($frontMatter, 2);

        return sprintf(self::FM_OBJ_TEMPLATE, $fm, $body);
    }

    ///
    // Misc Functions
    ///

    protected function bookCollectionProvider($jailed = false)
    {
        $cm = new CollectionManager(
            $this->getMockMarkupEngineManager(),
            $this->getMock(Configuration::class),
            $this->getMockEventDistpatcher(),
            $this->getMockLogger()
        );
        $cm->parseCollections([
            [
                'name' => 'books',
                'folder' => 'tests/allejo/stakx/Test/assets/MyBookCollection/',
            ],
        ]);

        return (!$jailed) ? $cm->getCollections() : $cm->getJailedCollections();
    }
}
