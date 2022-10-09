<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Filesystem\File;

class StaticPageView extends BasePageView implements TemplateReadyDocument
{
    private ?JailedDocument $jailInstance = null;

    /** @var JailedDocument[] */
    private ?array $jailedChildPageViews = null;

    /** @var self[] */
    private array $childPageViews = [];

    /**
     * StaticPageView constructor.
     */
    public function __construct(File $file)
    {
        $this->type = BasePageView::STATIC_TYPE;

        parent::__construct($file);
    }

    /**
     * {@inheritdoc}
     */
    public function createJail(): JailedDocument
    {
        if ($this->jailInstance === null) {
            $whiteListedFunctions = array_merge(self::$whiteListedFunctions, [
            ]);

            $jailedFunctions = [
                'getChildren' => 'getJailedChildren',
            ];

            $this->jailInstance = new JailedDocument($this, $whiteListedFunctions, $jailedFunctions);
        }

        return $this->jailInstance;
    }

    /**
     * Get child PageViews.
     *
     * A child is defined as a static PageView whose URL has a parent. For example, a PageView with a URL of
     * `/gallery/france/` would have the PageView whose URL is `/gallery` as a parent.
     *
     * @return self[]
     */
    public function &getChildren(): array
    {
        return $this->childPageViews;
    }

    /**
     * Get the child PageViews.
     *
     * @return JailedDocument[]
     */
    public function getJailedChildren(): array
    {
        if ($this->jailedChildPageViews === null) {
            $this->jailedChildPageViews = [];

            foreach ($this->childPageViews as $key => &$child) {
                $this->jailedChildPageViews[$key] = $child->createJail();
            }
        }

        return $this->jailedChildPageViews;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return array_merge($this->getFrontMatter(), [
            'content' => $this->getContent(),
            'permalink' => $this->getPermalink(),
            'redirects' => $this->getRedirects(),
        ]);
    }

    protected function beforeCompile(): void
    {
        $this->buildPermalink(true);
    }
}
