<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Templating\Twig\Extension;

use allejo\stakx\Templating\Twig\Extension\GroupByFilter;
use allejo\stakx\Test\StakxTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class GroupByFilterTest extends StakxTestCase
{
    public function testGroupByFilter(): void
    {
        $original = [
            'a' => ['id' => 20, 'name' => 'chimpanzee'],
            'b' => ['id' => 40, 'name' => 'meeting'],
            'c' => ['id' => 20, 'name' => 'dynasty'],
            'd' => ['id' => 50, 'name' => 'chocolate'],
            'e' => ['id' => 10, 'name' => 'bananas'],
            'f' => ['id' => 50, 'name' => 'fantasy'],
            'g' => ['id' => 50, 'name' => 'football'],
        ];

        $expected = [
            '10' => [
                'e' => ['id' => 10, 'name' => 'bananas'],
            ],
            '20' => [
                'a' => ['id' => 20, 'name' => 'chimpanzee'],
                'c' => ['id' => 20, 'name' => 'dynasty'],
            ],
            '40' => [
                'b' => ['id' => 40, 'name' => 'meeting'],
            ],
            '50' => [
                'd' => ['id' => 50, 'name' => 'chocolate'],
                'f' => ['id' => 50, 'name' => 'fantasy'],
                'g' => ['id' => 50, 'name' => 'football'],
            ],
        ];

        $gbFilter = new GroupByFilter();
        $grouped = $gbFilter($original, 'id');

        $this->assertEquals($expected, $grouped);
    }

    public function testGroupByFilterDotNotation(): void
    {
        $original = [
            'a' => ['metadata' => ['id' => 20], 'name' => 'chimpanzee'],
            'b' => ['metadata' => ['id' => 40], 'name' => 'meeting'],
            'c' => ['metadata' => ['id' => 20], 'name' => 'dynasty'],
            'd' => ['metadata' => ['id' => 50], 'name' => 'chocolate'],
            'e' => ['metadata' => ['id' => 10], 'name' => 'bananas'],
            'f' => ['metadata' => ['id' => 50], 'name' => 'fantasy'],
            'g' => ['metadata' => ['id' => 50], 'name' => 'football'],
        ];

        $expected = [
            '10' => [
                'e' => ['metadata' => ['id' => 10], 'name' => 'bananas'],
            ],
            '20' => [
                'a' => ['metadata' => ['id' => 20], 'name' => 'chimpanzee'],
                'c' => ['metadata' => ['id' => 20], 'name' => 'dynasty'],
            ],
            '40' => [
                'b' => ['metadata' => ['id' => 40], 'name' => 'meeting'],
            ],
            '50' => [
                'd' => ['metadata' => ['id' => 50], 'name' => 'chocolate'],
                'f' => ['metadata' => ['id' => 50], 'name' => 'fantasy'],
                'g' => ['metadata' => ['id' => 50], 'name' => 'football'],
            ],
        ];

        $gbFilter = new GroupByFilter();
        $grouped = $gbFilter($original, 'metadata.id');

        $this->assertEquals($expected, $grouped);
    }

    public function testGroupByFilterContentItems(): void
    {
        $books = $this->bookCollectionProvider()['books'];
        $filter = new GroupByFilter();
        $grouped = $filter($books, 'publisher');

        $this->assertArrayHasKey('Candlewick', $grouped);
        $this->assertArrayHasKey('Random House Books for Young Readers', $grouped);

        foreach ($grouped as $publisher => $books) {
            foreach ($books as $book) {
                $this->assertEquals($publisher, $book['publisher']);
            }
        }
    }

    public function testGroupByFilterBooleanFrontMatterKey(): void
    {
        $books = $this->bookCollectionProvider()['books'];
        $filter = new GroupByFilter();
        $grouped = $filter($books, 'completed');

        $this->assertArrayHasKey('true', $grouped);
        $this->assertArrayHasKey('false', $grouped);

        foreach ($grouped['true'] as $item) {
            $this->assertTrue($item['completed']);
        }

        foreach ($grouped['false'] as $item) {
            $this->assertFalse($item['completed']);
        }
    }

    public function testGroupByFilterNullFrontMatterKey(): void
    {
        $books = $this->bookCollectionProvider()['books'];
        $filter = new GroupByFilter();
        $grouped = $filter($books, 'non-existent-key');

        $this->assertCount(0, $grouped);
    }

    public function testGroupByFilterArrayThrowsWarning(): void
    {
        $this->expectWarning();

        $books = $this->bookCollectionProvider()['books'];
        $filter = new GroupByFilter();
        $filter($books, 'animals');
    }
}
