<?php

namespace allejo\stakx\tests;

use allejo\stakx\Object\ContentItem;

class JailObjectTests extends \PHPUnit_Stakx_TestCase
{
    public function testJailWhiteListFunction ()
    {
        $contentItem = $this->createVirtualFile(ContentItem::class);
        $jailable = $contentItem->createJail();
        $content = $jailable->getContent();

        $this->assertNotEmpty($content);
    }

    public function testJailFrontMatter ()
    {
        $value = "super bacon!";

        $contentItem = $this->createVirtualFile(ContentItem::class, array('value' => $value));
        $jailable = $contentItem->createJail();

        $this->assertEquals($value, $jailable->value);
    }

    public function testJailInvalidFunction ()
    {
        $this->setExpectedException(\BadMethodCallException::class);

        /** @var ContentItem $contentItem */
        $contentItem = $this->createVirtualFile(ContentItem::class);
        $jailable = $contentItem->createJail();
        $jailable->getLineOffset();
    }
}