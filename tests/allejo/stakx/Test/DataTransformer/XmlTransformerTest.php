<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\DataTransformer;

use allejo\stakx\DataTransformer\XmlTransformer;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class XmlTransformerTest extends PHPUnit_Stakx_TestCase
{
    public function testValidXmlData()
    {
        $file = <<<FILE
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

    public function testInvalidXmlData()
    {
        $file = <<<FILE
{ "message": "some fancy json" }
FILE;

        $actual = XmlTransformer::transformData($file);
        $expected = [];

        $this->assertEquals($expected, $actual);
    }
}
