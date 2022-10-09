<?php declare(strict_types=1);

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
    final public const NAME = 'compile.template_creation';

    public function __construct(private readonly BasePageView $pageView, private readonly TemplateInterface $template, private $theme)
    {
    }

    public function getPageView()
    {
        return clone $this->pageView;
    }

    public function getTemplate()
    {
        return clone $this->template;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }
}
