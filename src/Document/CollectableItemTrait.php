<?php declare(strict_types=1);

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
    protected ?string $namespace;

    /** @var DynamicPageView[] */
    protected array $parentPageViews;

    /**
     * {@inheritdoc}
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespace(?string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function &getParentPageViews(): array
    {
        return $this->parentPageViews;
    }

    /**
     * {@inheritdoc}
     */
    public function saveParentPageView(DynamicPageView $pageView): void
    {
        $this->parentPageViews[] = &$pageView;
    }
}
