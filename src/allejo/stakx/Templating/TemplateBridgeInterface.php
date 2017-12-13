<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating;

use Psr\Log\LoggerAwareInterface;

/**
 * This interface is used for bridging template engines for stakx to be able to use them.
 */
interface TemplateBridgeInterface extends LoggerAwareInterface
{
    /**
     * Set or update a global variable within the template engine.
     *
     * These will be values that are **always** available to all templates that are compiled by stakx.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function setGlobalVariable($key, $value);

    /**
     * Return a template that'll be used to compile templates with data.
     *
     * @param string $templateContent
     *
     * @throws TemplateErrorInterface When an error occurs while preparing/compiling a template for use.
     *
     * @return TemplateInterface
     */
    public function createTemplate($templateContent);
}
