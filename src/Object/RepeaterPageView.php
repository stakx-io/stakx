<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Object;

use allejo\stakx\FrontMatter\ExpandedValue;

class RepeaterPageView extends PageView
{
    /**
     * @var \ArrayIterator
     */
    private $permalinksIterator;

    /**
     * @var \ArrayIterator
     */
    private $redirectsIterator;

    private $permalinks;
    private $redirects;

    private $configured;

    public function __construct($filePath)
    {
        parent::__construct($filePath);

        $this->frontMatterBlacklist = array();
        $this->type = PageView::REPEATER_TYPE;
        $this->configured = false;
    }

    /**
     * @return ExpandedValue[]
     */
    public function getRepeaterPermalinks ()
    {
        $this->configureValues();

        return $this->permalinks;
    }

    public function getRepeaterRedirects ()
    {
        $this->configureValues();

        return $this->redirects;
    }

    public function bump ()
    {
        $this->permalink = $this->permalinksIterator->current()->getEvaluated();
        $this->redirects = $this->redirectsIterator->current();

        $this->permalinksIterator->next();
        $this->redirectsIterator->next();
    }

    private function configureValues ()
    {
        if ($this->configured) { return; }

        $this->getFrontMatter();
        $evaluated = $this->frontMatter['permalink'];

        $this->permalinks = $evaluated[0];
        array_shift($evaluated);
        $this->redirects = $evaluated;

        $this->permalinksIterator = new \ArrayIterator($this->permalinks);
        $this->redirectsIterator  = new \ArrayIterator($this->redirects);

        $this->configured = true;
    }
}