<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\DataTransformer;

use allejo\stakx\DataTransformer\XmlTransformer;
use allejo\stakx\Test\StakxTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class XmlTransformerTest extends StakxTestCase
{
    public function testValidXmlData(): void
    {
        $file = <<<'FILE'
<note>
    <to attribute="attr value">Tove</to>
    <from>Jani</from>
    <heading>Reminder</heading>
    <body>Don't forget me this weekend!</body>
</note>
FILE;

        $actual = XmlTransformer::transformData($file);
        $expected = [
            'to' => 'Tove',
            'from' => 'Jani',
            'heading' => 'Reminder',
            'body' => "Don't forget me this weekend!",
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testInvalidXmlData(): void
    {
        $file = <<<'FILE'
{ "message": "some fancy json" }
FILE;

        $actual = XmlTransformer::transformData($file);
        $expected = [];

        $this->assertEquals($expected, $actual);
    }
}
