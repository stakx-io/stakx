<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\DataTransformer;

use allejo\stakx\DataTransformer\CsvTransformer;
use allejo\stakx\DataTransformer\DataTransformerManager;
use allejo\stakx\DataTransformer\JsonTransformer;
use allejo\stakx\DataTransformer\XmlTransformer;
use allejo\stakx\DataTransformer\YamlTransformer;
use allejo\stakx\Exception\UnsupportedDataTypeException;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class DataTransformerManagerTest extends PHPUnit_Stakx_TestCase
{
    private $transformer;

    public function setUp()
    {
        parent::setUp();

        $this->transformer = new DataTransformerManager();
        $this->transformer->addDataTransformers([
            new CsvTransformer(),
            new JsonTransformer(),
            new XmlTransformer(),
            new YamlTransformer(),
        ]);
    }

    public static function dataProviderExtensions()
    {
        return [
            [CsvTransformer::class, 'csv'],
            [JsonTransformer::class, 'json'],
            [XmlTransformer::class, 'xml'],
            [YamlTransformer::class, 'yaml'],
            [YamlTransformer::class, 'yml'],
        ];
    }

    /**
     * @dataProvider dataProviderExtensions
     *
     * @param mixed $class
     * @param mixed $extension
     */
    public function testCorrectTransformer($class, $extension)
    {
        $this->assertInstanceOf($class, $this->transformer->getTransformer($extension));
    }

    public function testUnsupportedTransformerThrowsException()
    {
        $this->setExpectedException(UnsupportedDataTypeException::class);

        $this->transformer->getTransformer('fake-extension');
    }
}
