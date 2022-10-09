<?php declare(strict_types=1);

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
     */
    public function getTemplateName(): string;

    /**
     * Get the parent template this template is extending.
     *
     * @return false|self returns false when template is not extending anything
     */
    public function getParentTemplate(): false|self;

    /**
     * Render the template with the given context.
     */
    public function render(array $context = []): string;
}
