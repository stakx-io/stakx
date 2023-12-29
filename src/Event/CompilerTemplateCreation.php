<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\BasePageView;
use allejo\stakx\Templating\TemplateInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A notification only event for when a template is created by the Compiler.
 *
 * @since 0.2.0
 */
class CompilerTemplateCreation extends Event
{
    public const NAME = 'compile.template_creation';

    private BasePageView $pageView;
    private TemplateInterface $template;
    private string $theme;

    public function __construct(BasePageView $pageView, TemplateInterface $template, string $theme)
    {
        $this->pageView = $pageView;
        $this->template = $template;
        $this->theme = $theme;
    }

    public function getPageView(): BasePageView
    {
        return clone $this->pageView;
    }

    public function getTemplate(): TemplateInterface
    {
        return clone $this->template;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }
}
