<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

use allejo\stakx\Exception\UnsupportedDataTypeException;

class DataTransformerManager
{
    private $transformers;

    public function __construct()
    {
        $this->transformers = [];
    }

    public function addDataTransformers(/*iterable*/ $dataTransformers)
    {
        foreach ($dataTransformers as $dataTransformer)
        {
            $this->addDataTransformer($dataTransformer);
        }
    }

    public function addDataTransformer(DataTransformer $transformer)
    {
        foreach ($transformer->getExtensions() as $extension)
        {
            $this->transformers[$extension] = $transformer;
        }
    }

    public function getTransformer($extension)
    {
        if (isset($this->transformers[$extension]))
        {
            return $this->transformers[$extension];
        }

        throw new UnsupportedDataTypeException($extension, 'There is no support to handle this file extension.');
    }
}
