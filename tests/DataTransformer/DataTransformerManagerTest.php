<?php declare(strict_types=1);

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
use allejo\stakx\Test\StakxTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class DataTransformerManagerTest extends StakxTestCase
{
    private $transformer;

    public function setUp(): void
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

    public static function provideCorrectTransformerCases(): iterable
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
     * @dataProvider provideCorrectTransformerCases
     */
    public function testCorrectTransformer(mixed $class, mixed $extension): void
    {
        $this->assertInstanceOf($class, $this->transformer->getTransformer($extension));
    }

    public function testUnsupportedTransformerThrowsException(): void
    {
        $this->expectException(UnsupportedDataTypeException::class);

        $this->transformer->getTransformer('fake-extension');
    }
}
