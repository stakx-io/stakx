<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test;

use __;
use allejo\stakx\AssetEngine\AssetEngineManager;
use allejo\stakx\Configuration;
use allejo\stakx\Document\FrontMatterDocument;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Filesystem\WritableFolder;
use allejo\stakx\Logger;
use allejo\stakx\Manager\AssetManager;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\Manager\MenuManager;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\MarkupEngine\MarkdownEngine;
use allejo\stakx\MarkupEngine\MarkupEngineManager;
use allejo\stakx\MarkupEngine\PlainTextEngine;
use allejo\stakx\MarkupEngine\RstEngine;
use allejo\stakx\RedirectMapper;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use allejo\stakx\Templating\Twig\TwigExtension;
use allejo\stakx\Templating\Twig\TwigStakxBridge;
use allejo\stakx\Templating\Twig\TwigStakxBridgeFactory;
use BadFunctionCallException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Yaml;

abstract class StakxTestCase extends TestCase
{
    public const FM_OBJ_TEMPLATE = "---\n%s\n---\n\n%s";

    protected vfsStreamDirectory $rootDir;

    protected vfsStreamFile $dummyFile;

    protected ?string $assetFolder = null;

    public function setUp(): void
    {
        $this->dummyFile = vfsStream::newFile('stakx.html.twig');
        $this->rootDir = vfsStream::setup();

        Service::resetRuntimeFlags();

        Service::setWorkingDirectory(null);
        Service::setRuntimeFlag(RuntimeStatus::USING_HIGHLIGHTER);

        // Inspect the VFS as an array
        // vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure();
    }

    public function tearDown(): void
    {
        if ($this->assetFolder !== null) {
            fs::remove($this->assetFolder);
        }
    }

    //
    // Assertion Functions
    //

    protected function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertNotFalse(strpos($haystack, $needle), $message);
    }

    protected function assertFileContains(string $fileContent, string $filePath, string $message = ''): void
    {
        ($filePath[strlen($filePath) - 1] === '/') && $filePath .= 'index.html';

        $contents = file_get_contents($filePath);

        $this->assertStringContains($fileContent, $contents, $message);
    }

    //
    // Filesystem Functions
    //

    /**
     * Create a temporary folder where temporary file writes will be made to.
     */
    protected function createAssetFolder(string $folderName): void
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
     * @return string Path to the temporary file; relative to the project's root
     */
    protected function createPhysicalFile(string $fileName, string $content): string
    {
        $folder = new WritableFolder($this->assetFolder);
        $folder->writeFile($fileName, $content);

        return fs::appendPath($this->assetFolder, $fileName);
    }

    /**
     * Write a file to the virtual filesystem.
     *
     * This file will be deleted at each tearDown().
     *
     * @return string the URL of the file on the virtual filesystem
     */
    protected function createVirtualFile(string $filename, string $content): string
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
     * @return object An instance of $classType
     */
    protected function createDocumentOfType(string $classType, string $filename, string $content): object
    {
        $file = $this->createVirtualFile($filename, $content);

        return new $classType(new File($file));
    }

    /**
     * Create an object of a given type following the Front Matter format.
     *
     *
     * @return object An instance of $classType
     */
    protected function createFrontMatterDocumentOfType(string $classType, ?string $filename = null, array $frontMatter = [], string $content = 'Body Text'): object
    {
        $body = $this->buildFrontMatterTemplate($frontMatter, $content);

        if (!$filename) {
            $filename = hash('sha256', (string)random_int(0, mt_getrandmax()));
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
     */
    protected function createMultipleFrontMatterDocumentsOfType($classType, $elements): array
    {
        $results = [];

        foreach ($elements as $element) {
            $filename = $element['filename'] ?? null;
            $frontMatter = (!isset($element['frontmatter']) || empty($element['frontmatter'])) ? [] : $element['frontmatter'];
            $body = $element['body'] ?? 'Body Text';

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
     */
    protected function createFileObjectFromPath($filePath): File
    {
        return new File($filePath);
    }

    //
    // Mock Objects
    //

    protected function getMockAssetEngineManager(): MockBuilder|AssetEngineManager
    {
        return new AssetEngineManager();
    }

    protected function getMockAssetManager(): AssetManager
    {
        return new AssetManager($this->getMockEventDispatcher(), $this->getMockLogger());
    }

    protected function getMockConfiguration(): MockObject|Configuration
    {
        $stub = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $stub->method('getConfiguration')->willReturn([]);
        $stub->method('getTwigAutoescape')->willReturn(false);

        return $stub;
    }

    protected function getMockPageManager(): PageManager|MockObject
    {
        $stub = $this->getMockBuilder(PageManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $stub->method('getJailedStaticPageViews')->willReturn([]);

        return $stub;
    }

    protected function getMockMenuManager(): MockObject|MenuManager
    {
        $stub = $this->getMockBuilder(MenuManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $stub->method('getSiteMenu')->willReturn([]);

        return $stub;
    }

    protected function getMockCollectionManager(): MockObject|CollectionManager
    {
        $stub = $this->getMockBuilder(CollectionManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $stub->method('getJailedCollections')->willReturn([]);

        return $stub;
    }

    protected function getMockDataManager(): MockObject|DataManager
    {
        $stub = $this->getMockBuilder(DataManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $stub->method('getJailedDataItems')->willReturn([]);

        return $stub;
    }

    protected function getMockTwigExtension(): TwigExtension
    {
        // too lazy to actually mock all the methods... just create an actual instance of and dub it a "mock" to match
        // all the other mock methods. if this causes problems: sorry, future me!
        return new TwigExtension($this->getMockMarkupEngineManager());
    }

    protected function getMockTemplateBridge(): TwigStakxBridge
    {
        return TwigStakxBridgeFactory::createTwigEnvironment(
            $this->getMockConfiguration(),
            $this->getMockTwigExtension(),
            $this->getMockLogger()
        );
    }

    protected function getMockMarkupEngineManager(): MarkupEngineManager
    {
        // Just trying to keep the naming convention the same, but create an actual markup engine manager since it's
        // necessary in a lot of the unit tests
        $markupEngine = new MarkupEngineManager();

        $markupEngine->addMarkupEngines([
            new MarkdownEngine($this->getMockAssetManager()),
            new RstEngine($this->getMockAssetManager()),
            new PlainTextEngine($this->getMockAssetManager()),
        ]);

        return $markupEngine;
    }

    protected function getMockRedirectMapper(): RedirectMapper|MockObject
    {
        $stub = $this->getMockBuilder(RedirectMapper::class)
            ->getMock()
        ;

        $stub->method('getRedirects')->willReturn([]);

        return $stub;
    }

    /**
     * Get a mock EventDispatcher.
     */
    protected function getMockEventDispatcher(): MockObject|EventDispatcherInterface
    {
        return $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
    }

    /**
     * Get a mock logger.
     */
    protected function getMockLogger(): MockObject|LoggerInterface
    {
        return $this->getMockBuilder(LoggerInterface::class)->getMock();
    }

    /**
     * Get a real logger instance that will save output to the console.
     */
    protected function getReadableLogger(): Logger
    {
        stream_filter_register('intercept', StreamInterceptor::class);
        $stakxLogger = new Logger(new ConsoleOutput());
        stream_filter_append($stakxLogger->getOutputInterface()->getStream(), 'intercept');

        return $stakxLogger;
    }

    //
    // Utility Functions
    //

    /**
     * Get the directory of the unit tests.
     */
    protected static function getTestRoot(): string
    {
        return __DIR__;
    }

    /**
     * Generate a FrontMatter-ready syntax to be used as a file's content.
     */
    protected function buildFrontMatterTemplate(array $frontMatter = [], string $body = 'Body text'): string
    {
        $fm = (empty($frontMatter)) ? '' : Yaml::dump($frontMatter, 2);

        return sprintf(self::FM_OBJ_TEMPLATE, $fm, $body);
    }

    /**
     * @param array<string, mixed> $namedParams
     *
     * @throws ReflectionException
     */
    protected function invokeClassFunctionWithNamedParams(string $cls, string $method, array $namedParams = []): mixed
    {
        $clsReflection = new ReflectionClass($cls);
        $fxns = $clsReflection->getMethods();

        /** @var ReflectionMethod $fxnToCall */
        $fxnToCall = __::chain($fxns)
            ->filter(fn (ReflectionMethod $fxn) => $fxn->getName() === $method)
            ->get(0, null)
            ->value()
        ;

        if ($fxnToCall === null) {
            throw new BadFunctionCallException(sprintf('No function by the name of "%s" in this class', $method));
        }

        $arguments = $fxnToCall->getParameters();
        $callUserFuncArray = [];

        /** @var ReflectionParameter $argument */
        foreach ($arguments as $argument) {
            if (isset($namedParams[$argument->getName()])) {
                $callUserFuncArray[] = $namedParams[$argument->getName()];
            } else {
                $callUserFuncArray[] = $argument->getDefaultValue();
            }
        }

        return $fxnToCall->invoke(null, ...$callUserFuncArray);
    }

    //
    // Misc Functions
    //

    protected function bookCollectionProvider($jailed = false): array
    {
        $cm = new CollectionManager(
            $this->getMockMarkupEngineManager(),
            $this->getMockConfiguration(),
            $this->getMockTemplateBridge(),
            $this->getMockEventDispatcher(),
            $this->getMockLogger()
        );
        $cm->parseCollections([
            [
                'name' => 'books',
                'folder' => 'tests/assets/MyBookCollection/',
            ],
        ]);

        return (!$jailed) ? $cm->getCollections() : $cm->getJailedCollections();
    }
}
