<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\BasePageView;
use allejo\stakx\Templating\TemplateInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * A notification only event for when a template is created by the Compiler.
 *
 * @since 0.2.0
 */
class CompileProcessTemplateCreation extends Event
{
    const NAME = 'compile.process.template_creation';

    private $pageView;
    private $template;
    private $theme;

    public function __construct(BasePageView $pageView, TemplateInterface $template, $theme)
    {
        $this->pageView = $pageView;
        $this->template = $template;
        $this->theme = $theme;
    }

    /**
     * @return BasePageView
     */
    public function getPageView()
    {
        return clone $this->pageView;
    }

    /**
     * @return TemplateInterface
     */
    public function getTemplate()
    {
        return clone $this->template;
    }

    /**
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }
}
