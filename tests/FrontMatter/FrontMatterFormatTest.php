<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\FrontMatter;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Exception\InvalidSyntaxException;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Test\StakxTestCase;

/**
 * @internal
 *
 * @covers \allejo\stakx\Document\FrontMatterDocument
 */
class FrontMatterFormatTest extends StakxTestCase
{
    public function testBlankLineAfterFrontMatter(): void
    {
        $this->expectNotToPerformAssertions();

        $expectedFormat = <<<'LINE'
---
foo: bar
---

Some content
LINE;

        $this->createFile(ContentItem::class, $expectedFormat);
    }

    public function testNoBlankLineAfterFrontMatter(): void
    {
        $this->expectNotToPerformAssertions();

        $expectedFormat = <<<'LINE'
---
foo: bar
---
Some content
LINE;

        $this->createFile(ContentItem::class, $expectedFormat);
    }

    public function testEmptyFrontMatter(): void
    {
        $this->expectNotToPerformAssertions();

        $expectedFormat = <<<'LINE'
---
---

Some content
LINE;

        $this->createFile(ContentItem::class, $expectedFormat);
    }

    public function testEmptyWithLineFrontMatter(): void
    {
        $this->expectNotToPerformAssertions();

        $expectedFormat = <<<'LINE'
---


---

Some content
LINE;

        $this->createFile(ContentItem::class, $expectedFormat);
    }

    public function testMultipleBlankLinesAfterFrontMatter(): void
    {
        $this->expectNotToPerformAssertions();

        $expectedFormat = <<<'LINE'
---
foo: bar
---


Some content
LINE;

        $this->createFile(ContentItem::class, $expectedFormat);
    }

    public function testNoNewLineAfterFrontMatterThrowsException(): void
    {
        $this->expectException(InvalidSyntaxException::class);
        $expectedFormat = <<<'LINE'
---
foo: bar
---Some content
LINE;

        $this->createFile(ContentItem::class, $expectedFormat);
    }

    public function testNoNewLineAtBeginningOfFrontMatterThrowsException(): void
    {
        $this->expectException(InvalidSyntaxException::class);
        $expectedFormat = <<<'LINE'
---foo: bar
---

Some content
LINE;

        $this->createFile(ContentItem::class, $expectedFormat);
    }

    public function testFrontMatterInOneLineThrowsException(): void
    {
        $this->expectException(InvalidSyntaxException::class);
        $expectedFormat = <<<'LINE'
---foo: bar---

Some content
LINE;

        $this->createFile(ContentItem::class, $expectedFormat);
    }

    public function testNoNewLineBeforeFrontMatterClosesThrowsException(): void
    {
        $this->expectException(InvalidSyntaxException::class);
        $expectedFormat = <<<'LINE'
---
foo: bar---

Some content
LINE;

        $this->createFile(ContentItem::class, $expectedFormat);
    }

    public function testNoBodyThrowsException(): void
    {
        $this->expectException(InvalidSyntaxException::class);
        $expectedFormat = <<<'LINE'
---
foo: bar---
LINE;

        $this->createFile(ContentItem::class, $expectedFormat);
    }

    public function testWhiteSpaceBodyThrowsException(): void
    {
        $this->expectException(InvalidSyntaxException::class);
        $expectedFormat = <<<'LINE'
---
foo: bar---


LINE;

        $this->createFile(ContentItem::class, $expectedFormat);
    }

    public function testEmptyFrontMatterOneLineThrowsException(): void
    {
        $this->expectException(InvalidSyntaxException::class);
        $expectedFormat = <<<'LINE'
------

Some content
LINE;

        $this->createFile(ContentItem::class, $expectedFormat);
    }

    /**
     * @param class-string $classType
     * @param string       $content
     */
    private function createFile(string $classType, string $content): void
    {
        $this->dummyFile
            ->setContent($content)
            ->at($this->rootDir)
        ;

        $url = $this->dummyFile->url();

        new $classType(new File($url));
    }
}
