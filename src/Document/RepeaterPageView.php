<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Filesystem\File;
use allejo\stakx\FrontMatter\ExpandedValue;

class RepeaterPageView extends BasePageView implements TemplateReadyDocument
{
    /** @var ExpandedValue[] All of the expanded permalinks. */
    private $permalinks;

    /** @var ExpandedValue[][] All of expanded redirects that should point to the respective permalink; this is estimated by index. */
    private $redirectLinks;

    /**
     * RepeaterPageView constructor.
     */
    public function __construct(File $file)
    {
        parent::__construct($file);

        $this->type = BasePageView::REPEATER_TYPE;
    }

    /**
     * Get the permalink matching all the placeholders for a Repeater.
     *
     * @param array $where
     *
     * @return null|string
     */
    public function _getPermalinkWhere(array $where)
    {
        foreach ($this->permalinks as $expandedValue)
        {
            if ($expandedValue->getIterators() === $where)
            {
                return $expandedValue->getEvaluated();
            }
        }

        return null;
    }

    /**
     * Get the expanded values for the permalinks to this PageView.
     *
     * @return ExpandedValue[]
     */
    public function getRepeaterPermalinks()
    {
        return $this->permalinks;
    }

    /**
     * Get the expanded values for the redirects pointing to this PageView.
     *
     * @return ExpandedValue[][]
     */
    public function getRepeaterRedirects()
    {
        return $this->redirectLinks;
    }

    /**
     * Configure permalinks from expanded values internally.
     */
    public function configurePermalinks()
    {
        $evaluated = $this->frontMatter['permalink'];

        $this->permalinks = $evaluated[0];
        array_shift($evaluated);
        $this->redirectLinks = $evaluated;
    }

    /**
     * {@inheritdoc}
     */
    public function buildPermalink($force = false)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createJail()
    {
        $whitelist = array_merge(self::$whiteListedFunctions, [
            '_getPermalinkWhere',
        ]);

        return new JailedDocument($this, $whitelist);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [];
    }
}
