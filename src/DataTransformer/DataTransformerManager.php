<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

use allejo\stakx\Exception\UnsupportedDataTypeException;

class DataTransformerManager
{
    /** @var DataTransformerInterface[] */
    private array $transformers;

    public function __construct()
    {
        $this->transformers = [];
    }

    public function addDataTransformers(iterable $dataTransformers): void
    {
        foreach ($dataTransformers as $dataTransformer)
        {
            $this->addDataTransformer($dataTransformer);
        }
    }

    public function addDataTransformer(DataTransformerInterface $transformer): void
    {
        foreach ($transformer->getExtensions() as $extension)
        {
            $this->transformers[$extension] = $transformer;
        }
    }

    public function getTransformer($extension): DataTransformerInterface
    {
        if (isset($this->transformers[$extension]))
        {
            return $this->transformers[$extension];
        }

        throw new UnsupportedDataTypeException($extension, 'There is no support to handle this file extension.');
    }
}
