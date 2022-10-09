<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\test\Templating\Twig\Extension;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Templating\Twig\Extension\SelectFilter;
use allejo\stakx\Test\StakxTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class SelectFilterTest extends StakxTestCase
{
    public function testSelectFilterWithDefaultSettings(): void
    {
        $posts = $this->getDummyCollection();

        $filter = new SelectFilter();
        $results = $filter($posts, 'tags');

        $this->assertCount(4, $results);

        foreach (['red', 'blue', 'green', 'orange'] as $value) {
            $this->assertContains($value, $results);
        }
    }

    public function testSelectFilterWithFlattenOnly(): void
    {
        $posts = $this->getDummyCollection();
        $filter = new SelectFilter();
        $results = $filter($posts, 'tags', false);

        $this->assertCount(3, $results);
        $this->assertContains(['red', 'blue'], $results);
    }

    public function testSelectFilterWithFlattenNoDistinct(): void
    {
        $posts = $this->getDummyCollection();
        $filter = new SelectFilter();
        $results = $filter($posts, 'tags', true, false);

        $this->assertCount(7, $results);
    }

    public function testSelectFilterWithMultidimensionalArray(): void
    {
        $mdArray = [
            ['tags' => ['red', ['nested', 'color', 'blue']]],
            ['tags' => ['green', 'orange']],
            ['tags' => ['blue', 'purple']],
        ];
        $filter = new SelectFilter();
        $results = $filter($mdArray, 'tags');

        $this->assertCount(7, $results);
        $this->assertContains('nested', $results);
    }

    public function testSelectFilterWithNullValuesKeepsNull(): void
    {
        $nullArray = [
            ['tags' => ['hello', 'beautiful']],
            ['tags' => null],
            ['cats' => '123'],
            ['tags' => 'world'],
        ];
        $filter = new SelectFilter();
        $results = $filter($nullArray, 'tags', true, true, false);

        $this->assertEquals(['hello', 'beautiful', null, 'world'], $results);
    }

    public function testSelectFilterDropsNullValues(): void
    {
        $nullArray = [
            ['tags' => ['hello', 'beautiful']],
            ['tags' => null],
            ['tags' => 'world'],
        ];
        $filter = new SelectFilter();
        $results = $filter($nullArray, 'tags');

        $this->assertEquals(['hello', 'beautiful', 'world'], $results);
    }

    public function testSelectFilterKeepsDuplicateNull(): void
    {
        $nullArray = [
            ['tags' => ['hello', 'beautiful']],
            ['tags' => null],
            ['tags' => null],
            ['tags' => 'world'],
        ];
        $filter = new SelectFilter();
        $results = $filter($nullArray, 'tags', true, false, false);

        $this->assertEquals(['hello', 'beautiful', null, null, 'world'], $results);
    }

    public function testSelectFilterKeepsDistinctNull(): void
    {
        $nullArray = [
            ['tags' => ['hello', 'beautiful']],
            ['tags' => null],
            ['tags' => null],
            ['tags' => 'world'],
        ];
        $filter = new SelectFilter();
        $results = $filter($nullArray, 'tags', true, true, false);

        $this->assertEquals(['hello', 'beautiful', null, 'world'], $results);
    }

    public function testSelectFilterDotNotation(): void
    {
        $nestedArray = [
            ['metadata' => ['tags' => ['php', 'programming']]],
            ['metadata' => ['tags' => ['cooking']]],
            ['metadata' => ''],
            [],
        ];
        $filter = new SelectFilter();
        $results = $filter($nestedArray, 'metadata.tags');

        $this->assertEquals(['php', 'programming', 'cooking'], $results);
    }

    private function getDummyCollection()
    {
        return $this->createMultipleFrontMatterDocumentsOfType(ContentItem::class, [
            ['frontmatter' => ['tags' => ['red', 'blue']]],
            ['frontmatter' => ['tags' => ['red', 'green']]],
            ['frontmatter' => ['tags' => ['green', 'blue', 'orange']]],
        ]);
    }
}
