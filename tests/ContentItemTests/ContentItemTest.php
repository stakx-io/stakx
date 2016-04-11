<?php

namespace allejo\stakx\tests;

use allejo\stakx\Object\ContentItem;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Filesystem\Exception\IOException;

class ContentItemTests extends PHPUnit_Framework_TestCase
{
    const TargetClass = '\allejo\stakx\Object\ContentItem';

    public function testContentItemWithEmptyFileThrowsIOException ()
    {
        $this->setExpectedException(IOException::class);

        $root = vfsStream::setup();
        $file = vfsStream::newFile('foo.html.twig')->at($root);

        new ContentItem($file->url());
    }
}