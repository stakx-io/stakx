<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

/**
 * This interface defines what an object needs in order for a dynamic PageView to treat it as an item of a group of
 * files.
 */
trait CollectableItemTrait
{
    /** @var string */
    protected $namespace;

    /** @var DynamicPageView */
    protected $pageView;

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function &getParentPageView()
    {
        return $this->pageView;
    }

    /**
     * {@inheritdoc}
     */
    public function getJailedPageView()
    {
        return $this->pageView->createJail();
    }

    /**
     * {@inheritdoc}
     */
    public function setParentPageView(DynamicPageView &$pageView)
    {
        $this->pageView = &$pageView;
    }
}
