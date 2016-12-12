<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

use allejo\stakx\System\Filesystem;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use Symfony\Component\Yaml\Yaml;

abstract class PHPUnit_Stakx_TestCase extends PHPUnit_Framework_TestCase
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

    protected function createVirtualFile ($classType, $frontMatter = array(), $body = "Body Text")
    {
        $fm = (empty($frontMatter)) ? '' : Yaml::dump($frontMatter, 2);

        $this->dummyFile
            ->setContent(sprintf("---\n%s\n---\n\n%s", $fm, $body))
            ->at($this->rootDir);

        return (new $classType($this->dummyFile->url()));
    }
}