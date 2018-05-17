<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating;

/**
 * This template is used to bridge template objects from a template engine so stakx can use them.
 */
interface TemplateInterface
{
    /**
     * Get the name of the template.
     *
     * @return string
     */
    public function getTemplateName();

    /**
     * Get the parent template this template is extending.
     *
     * @return TemplateInterface|false returns false when template is not extending anything
     */
    public function getParentTemplate();

    /**
     * Render the template with the given context.
     *
     * @param array $context
     *
     * @return string
     */
    public function render(array $context = []);
}
