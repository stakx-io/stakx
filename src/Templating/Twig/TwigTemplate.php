<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use allejo\stakx\Templating\TemplateInterface;
use Twig\TemplateWrapper;

class TwigTemplate implements TemplateInterface
{
    public function __construct(private readonly TemplateWrapper $template)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateName(): string
    {
        return $this->template->getTemplateName();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentTemplate(): false|self
    {
        $parent = $this->template->getParent([]);

        if ($parent === false) {
            return false;
        }

        return new TwigTemplate($parent);
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $context = []): string
    {
        return $this->template->render($context);
    }
}
