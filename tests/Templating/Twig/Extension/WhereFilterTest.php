<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Templating\Twig\Extension;

use __;
use allejo\stakx\Document\ContentItem;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use allejo\stakx\Templating\Twig\Extension\WhereFilter;
use allejo\stakx\Test\StakxTestCase;
use Twig\Error\SyntaxError;

/**
 * @internal
 *
 * @covers \allejo\stakx\Templating\Twig\Extension\WhereFilter
 */
class WhereFilterTest extends StakxTestCase
{
    private array $dataset;

    public function setUp(): void
    {
        parent::setUp();

        Service::setRuntimeFlag(RuntimeStatus::USING_DRAFTS);

        $this->dataset = [
            [
                'name' => 'One Five',
                'slug' => 'chimpanzee',
                'cost' => 20,
                'tags' => ['fun', 'monkey', 'banana'],
                'author' => [
                    'fname' => 'John',
                    'lname' => 'Doe',
                ],
            ],
            [
                'name' => 'Two One',
                'slug' => 'meeting',
                'cost' => 40,
                'tags' => ['fun', 'purple', 'red', 'Bacon'],
            ],
            [
                'name' => 'Three Two',
                'slug' => 'dynasty',
                'cost' => 20,
                'tags' => ['monkey', 'animal', 'zoo'],
                'author' => [
                    'fname' => 'Joseph',
                    'lname' => 'Alan',
                ],
            ],
            [
                'name' => 'Four One',
                'slug' => 'chocolate',
                'cost' => 50,
                'tags' => ['fruit', 'port', 'computer'],
                'author' => [
                    'fname' => 'John',
                    'lname' => 'Doe',
                ],
            ],
            [
                'name' => 'Five Five',
                'slug' => 'bananas',
                'cost' => 10,
                'tags' => ['vegetable', 'purple', 'red', 'Bacon'],
                'author' => [
                    'fname' => 'Jane',
                    'lname' => 'Doe',
                ],
            ],
            [
                'name' => 'Six Three',
                'slug' => 'fantasy',
                'cost' => 50,
                'tags' => ['fruit', 'orange', 'purple'],
                'author' => [
                    'fname' => 'John',
                    'lname' => 'Doe',
                ],
            ],
        ];
    }

    public static function provideWhereFilterCases(): iterable
    {
        return [
            ['assertEquals', 'cost', '==', 50],
            ['assertEquals', 'cost', '==', 20],
            ['assertEquals', 'slug', '==', 'meeting'],
            ['assertEquals', 'author.fname', '==', 'John'],
            ['assertEquals', 'author.lname', '==', 'Doe'],

            // Weakly typed comparisons should fail
            ['assertEmpty', 'cost', '==', '50'],
            ['assertEmpty', 'cost', '==', '20'],
            ['assertNotEquals', 'cost', '!=', 10],
            ['assertNotEquals', 'cost', '!=', 20],
            ['assertNotEquals', 'slug', '!=', 'meeting'],
            ['assertGreaterThan', 'cost', '>', 20],
            ['assertEmpty', 'cost', '>', 50],
            ['assertGreaterThanOrEqual', 'cost', '>=', 40],
            ['assertGreaterThanOrEqual', 'cost', '>=', 50],
            ['assertLessThan', 'cost', '<', 40],
            ['assertEmpty', 'cost', '<', 10],
            ['assertLessThanOrEqual', 'cost', '<=', 50],
            ['assertLessThanOrEqual', 'cost', '<=', 30],
            ['assertContains', 'tags', '~=', 'purple'],
            ['assertStringContains', 'name', '~=', 'One'],
        ];
    }

    /**
     * @dataProvider provideWhereFilterCases
     *
     * @param string $fxn        The assertion function to test
     * @param string $key        The array key we'll be checking
     * @param string $comparison The comparison we'll be using
     * @param mixed  $value      The value we are looking for
     */
    public function testWhereFilter(string $fxn, string $key, string $comparison, mixed $value): void
    {
        $whereFilter = new WhereFilter();
        $filtered = $whereFilter($this->dataset, $key, $comparison, $value);

        if ($fxn === 'assertEmpty') {
            $this->assertEmpty($filtered);
        } else {
            foreach ($filtered as $item) {
                $this->{$fxn}($value, __::get($item, $key));
            }
        }
    }

    public function testInvalidFilterEmptyResult(): void
    {
        $this->expectException(SyntaxError::class);

        $whereFilter = new WhereFilter();
        $whereFilter($this->dataset, 'name', 'non-existent-comparison', 'the_map');
    }

    public function testInvalidKeyEmptyResult(): void
    {
        $whereFilter = new WhereFilter();
        $filtered = $whereFilter($this->dataset, 'non-existent-key', '==', 'the_map');

        $this->assertEmpty($filtered);
    }

    public function testGetTwigSimpleFilter(): void
    {
        $this->expectNotToPerformAssertions();

        WhereFilter::get();
    }

    public function testWhereFilterAgainstContentItem(): void
    {
        $elements = [
            $this->createFrontMatterDocumentOfType(ContentItem::class, null, [
                'aggregate' => 'toast',
                'category' => 'warm',
            ]),
            $this->createFrontMatterDocumentOfType(ContentItem::class, null, [
                'aggregate' => 'bacon',
                'category' => 'warm',
            ]),
            $this->createFrontMatterDocumentOfType(ContentItem::class, null, [
                'aggregate' => 'pancake',
                'category' => 'cold',
            ]),
        ];

        foreach ($elements as $element) {
            $element->evaluateFrontMatter();
        }

        $whereFilter = new WhereFilter();
        $filteredAggregate = $whereFilter($elements, 'aggregate', '==', 'toast');
        $filteredCategory = $whereFilter($elements, 'category', '==', 'warm');

        $this->assertCount(1, $filteredAggregate);
        $this->assertCount(2, $filteredCategory);
    }

    public static function provideWhereFilterContentItemAssertCountCases(): iterable
    {
        return [
            ['completed', '==', true, 3],
            ['completed', '==', false, 2],
            ['completed', '==', null, 1],
            ['completed', '!=', false, 4],
            ['completed', '~=', false, 0],
            ['page_count', '>=', 200, 2],
            ['page_count', '~=', 200, 0],
            ['shipping_weight', '>', 6, 3],
            ['shipping_weight', '>=', 12.6, 2],
            ['shipping_weight', '<', 12.6, 3],
            ['shipping_weight', '<=', 12.6, 5],
            ['publisher', '~=', 'Candle', 3],
            ['publisher', '~=', 'candle', 0],
            ['publisher', '~=', 'R', 2],
            ['publisher', '_=', 'candle', 3],
            ['publisher', '_=', 'r', 2],
            ['animals', '_=', 'Dog', 2],
            ['publisher', '/=', '/.wick.?/', 3],
        ];
    }

    /**
     * @dataProvider provideWhereFilterContentItemAssertCountCases
     *
     * @param string $fm         The front matter key we'll be checking
     * @param string $comparison The comparison we'll be using
     * @param mixed  $value      The value we're looking for the front matter to match
     * @param int    $count      The amount of entries we're expecting to match this rule
     */
    public function testWhereFilterContentItemAssertCount(string $fm, string $comparison, mixed $value, int $count): void
    {
        $collections = $this->bookCollectionProvider(true);
        $books = $collections['books'];
        $filter = new WhereFilter();

        $trueResults = $filter($books, $fm, $comparison, $value);
        $this->assertCount($count, $trueResults);
    }
}
