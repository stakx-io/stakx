<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test;

use allejo\stakx\Core\StakxLogger;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\System\Filesystem;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Yaml\Yaml;

abstract class PHPUnit_Stakx_TestCase extends \PHPUnit_Framework_TestCase
{
    const FM_OBJ_TEMPLATE = "---\n%s\n---\n\n%s";

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

    public function setUp()
    {
        $this->dummyFile    = vfsStream::newFile('stakx.html.twig');
        $this->rootDir      = vfsStream::setup();
        $this->fs           = new Filesystem();
    }

    //
    // Assert Functions
    //

    public function assertFileExistsAndContains ($filePath, $needle, $message = '')
    {
        $this->assertFileExists($filePath, $message);

        $contents = file_get_contents($filePath);

        $this->assertContains($needle, $contents, $message);
    }

    //
    // Utility Functions
    //

    protected function bookCollectionProvider ()
    {
        $cm = new CollectionManager();
        $cm->setLogger($this->loggerMock());
        $cm->parseCollections(array(
            array(
                'name'   => 'books',
                'folder' => 'tests/allejo/stakx/Test/assets/MyBookCollection/'
            )
        ));

        return $cm->getJailedCollections();
    }

    /**
     * @param  string $classType
     * @param  array  $frontMatter
     * @param  string $body
     *
     * @return mixed
     */
    protected function createVirtualFile ($classType, $frontMatter = array(), $body = "Body Text")
    {
        $fm = (empty($frontMatter)) ? '' : Yaml::dump($frontMatter, 2);

        $this->dummyFile
            ->setContent(sprintf("---\n%s\n---\n\n%s", $fm, $body))
            ->at($this->rootDir);

        return (new $classType($this->dummyFile->url()));
    }

    /**
     * Get a mock logger
     *
     * @return LoggerInterface
     */
    protected function loggerMock ()
    {
        return $this->getMock(LoggerInterface::class);
    }

    /**
     * Get a real logger instance that will save output to the console
     *
     * @return StakxLogger
     */
    protected function getReadableLogger ()
    {
        stream_filter_register('intercept', StreamInterceptor::class);
        $stakxLogger = new StakxLogger(new ConsoleOutput());
        stream_filter_append($stakxLogger->getOutputInterface()->getStream(), 'intercept');

        return $stakxLogger;
    }
}