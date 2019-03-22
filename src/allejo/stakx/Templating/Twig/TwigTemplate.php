<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use allejo\stakx\Templating\TemplateInterface;
use Twig\TemplateWrapper;

class TwigTemplate implements TemplateInterface
{
    private $template;

    public function __construct(TemplateWrapper $template)
    {
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateName()
    {
        return $this->template->getTemplateName();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentTemplate()
    {
        $parent = $this->template->getParent([]);

        if ($parent === false)
        {
            return false;
        }

        return new TwigTemplate($parent);
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $context = [])
    {
        return $this->template->render($context);
    }
}
