<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating;

class TwigTemplate implements TemplateInterface
{
    private $template;

    public function __construct(\Twig_Template $template)
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
        return $this->template->getParent([]);
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $context)
    {
        return $this->template->render($context);
    }
}
