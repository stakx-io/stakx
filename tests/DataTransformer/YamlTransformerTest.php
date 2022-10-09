<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\DataTransformer;

use allejo\stakx\DataTransformer\YamlTransformer;
use allejo\stakx\Test\StakxTestCase;
use DateTime;
use DateTimeZone;

/**
 * @internal
 *
 * @coversNothing
 */
class YamlTransformerTest extends StakxTestCase
{
    public function testValidYamlData(): void
    {
        $file = <<<'FILE'
month: January
events:
  - 2017-01-01
  - 2017-01-18
  - 2017-01-19
  - 2017-01-30
FILE;

        $tz = new DateTimeZone('UTC');

        $actual = YamlTransformer::transformData($file);
        $expected = [
            'month' => 'January',
            'events' => [
                new DateTime('2017-01-01', $tz),
                new DateTime('2017-01-18', $tz),
                new DateTime('2017-01-19', $tz),
                new DateTime('2017-01-30', $tz),
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testInvalidYamlData(): void
    {
        $file = "root:\tkey: 1";

        $actual = YamlTransformer::transformData($file);
        $expected = [];

        $this->assertEquals($expected, $actual);
    }
}
