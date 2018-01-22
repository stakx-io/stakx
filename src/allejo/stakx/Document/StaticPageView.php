<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Filesystem\File;

class StaticPageView extends BasePageView implements TemplateReadyDocument
{
    /** @var JailedDocument */
    private $jailInstance;

    /** @var JailedDocument[] */
    private $jailedChildPageViews;

    /** @var StaticPageView[] */
    private $childPageViews = [];

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
    public function createJail()
    {
        if ($this->jailInstance === null)
        {
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
     * @return StaticPageView[]
     */
    public function &getChildren()
    {
        return $this->childPageViews;
    }

    /**
     * Get the child PageViews.
     *
     * @return JailedDocument[]
     */
    public function getJailedChildren()
    {
        if ($this->jailedChildPageViews === null)
        {
            $this->jailedChildPageViews = [];

            foreach ($this->childPageViews as $key => &$child)
            {
                $this->jailedChildPageViews[$key] = $child->createJail();
            }
        }

        return $this->jailedChildPageViews;
    }

    /**
     * Get the PageView's body written in a templating language.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->bodyContent;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return array_merge($this->getFrontMatter(), [
            'content'   => $this->getContent(),
            'permalink' => $this->getPermalink(),
            'redirects' => $this->getRedirects(),
        ]);
    }

    protected function beforeCompile()
    {
        $this->buildPermalink(true);
    }
}
