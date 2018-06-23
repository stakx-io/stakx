<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

/**
 * This trait implements the methods required by the `CollectableItem` interface.
 */
trait CollectableItemTrait
{
    /** @var string */
    protected $namespace;

    /** @var DynamicPageView[] */
    protected $parentPageViews;

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
    public function &getParentPageViews()
    {
        return $this->parentPageViews;
    }

    /**
     * {@inheritdoc}
     */
    public function saveParentPageView(DynamicPageView &$pageView)
    {
        $this->parentPageViews[] = &$pageView;
    }
}
