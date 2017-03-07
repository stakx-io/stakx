<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Object;

use allejo\stakx\Object\Website;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class WebsiteTest extends PHPUnit_Stakx_TestCase
{
    private $website;

    public function setUp ()
    {
        parent::setUp();
        $this->markTestSkipped();

        /** @var OutputInterface $outputMock */
        $outputMock = $this->getMockBuilder(OutputInterface::class)->getMock();

        $this->website = new Website($outputMock);
        $this->website->setConfiguration(__DIR__ . '/../assets/ConfigurationFiles/build.yml');
        $this->website->build();
    }

    public function tearDown ()
    {
        $this->fs->remove(__DIR__ . '/output');
    }

    public function testDynamicPageViewOutputExists ()
    {
        $this->assertFileExists($this->fs->appendPath(__DIR__, 'output', 'books', 'tiger-rising', 'index.html'));
    }
}