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
     * An iterator for the permalinks used in order for this entity to be treated as a static PageView
     *
     * @var \ArrayIterator
     */
    private $permalinksIterator;

    /**
     * All of the expanded permalinks
     *
     * @var ExpandedValue[]
     */
    private $permalinks;

    /**
     * All of expanded redirects that should point to the respective permalink; this is estimated by index
     *
     * @var ExpandedValue[][]
     */
    private $redirectLinks;

    /**
     * {@inheritdoc}
     */
    public function __construct($filePath)
    {
        parent::__construct($filePath);

        $this->type = PageView::REPEATER_TYPE;

        $this->configureValues();
    }

    /**
     * Get the expanded values for the permalinks to this PageView
     *
     * @return ExpandedValue[]
     */
    public function getRepeaterPermalinks ()
    {
        return $this->permalinks;
    }

    /**
     * Get the expanded values for the redirects pointing to this PageView
     *
     * @return ExpandedValue[][]
     */
    public function getRepeaterRedirects ()
    {
        return $this->redirectLinks;
    }

    /**
     * When looping through permalinks in a RepeaterPageView, the permalink needs to be updated each time so that it may
     * behave as a static PageView.
     */
    public function bumpPermalink ()
    {
        $this->permalink = $this->permalinksIterator->current()->getEvaluated();
        $this->permalinksIterator->next();
    }

    /**
     * Rewind the permalink iterator to the beginning
     */
    public function resetPermalink ()
    {
        $this->permalinksIterator->rewind();
    }

    /**
     * Setup this object
     */
    private function configureValues ()
    {
        // Cause the Front Matter to be evaluated
        $this->getFrontMatter();

        $evaluated = $this->frontMatter['permalink'];

        $this->permalinks = $evaluated[0];
        array_shift($evaluated);
        $this->redirectLinks = $evaluated;

        $this->permalinksIterator = new \ArrayIterator($this->permalinks);
    }
}