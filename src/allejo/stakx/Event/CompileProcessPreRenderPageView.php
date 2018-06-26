<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * This event is fired before a PageView is rendered and allows you inject custom variables into the templates.
 *
 * @since 0.2.0
 */
class CompileProcessPreRenderPageView extends Event
{
    const NAME = 'compile.process.prerender_pageview';

    private $customVariables = [];
    private $pageViewType;

    public function __construct($pageViewType)
    {
        $this->pageViewType = $pageViewType;
    }

    public function getPageViewType()
    {
        return $this->pageViewType;
    }

    public function getCustomVariables()
    {
        if (!is_array($this->customVariables))
        {
            return [];
        }

        return $this->customVariables;
    }

    /**
     * Append custom variables that'll be made available as template variables in PageViews.
     *
     * @param array $customVariables
     *
     * @since 0.2.0
     */
    public function appendCustomVariables(array $customVariables)
    {
        $this->customVariables = array_merge($this->customVariables, $customVariables);
    }
}
