<?php

namespace allejo\stakx\FrontMatter;

class ExpandedValue
{
    /**
     * @var string
     */
    private $evaluated;

    /**
     * @var string[]
     */
    private $iterators;

    public function __construct($string)
    {
        $this->evaluated = $string;
    }

    public function __toString()
    {
        return $this->getEvaluated();
    }

    public function getEvaluated ()
    {
        return $this->evaluated;
    }

    public function setEvaluated ($string)
    {
        $this->evaluated = $string;
    }

    public function getIterators ()
    {
        return $this->iterators;
    }

    public function setIterator ($key, $value)
    {
        $this->iterators[$key] = $value;
    }
}