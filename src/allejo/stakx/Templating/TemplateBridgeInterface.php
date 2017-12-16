<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating;

use allejo\stakx\Compiler;
use Psr\Log\LoggerAwareInterface;

/**
 * This interface is used for bridging template engines for stakx to be able to use them.
 */
interface TemplateBridgeInterface extends LoggerAwareInterface
{
    /**
     * Whether or not this bridge has a profiler configured.
     *
     * @return bool
     */
    public function hasProfiler();

    /**
     * Set a profiler for this bridge.
     *
     * @param object|null $profiler
     *
     * @return void
     */
    public function setProfiler($profiler);

    /**
     * Get the output of this profiler that'll be written to the console.s
     *
     * @return string
     */
    public function getProfilerOutput(Compiler $compiler);

    /**
     * Get all of the references to either DataItems or ContentItems inside of given string.
     *
     * @param string $namespace   'collections' or 'data'
     * @param string $bodyContent The body we're looking through to find references
     *
     * @return array
     */
    public function getAssortmentDependencies($namespace, $bodyContent);

    /**
     * Get all of the "import", "from", and "include" dependencies from a template body.
     *
     * @param string $bodyContent The body we're looking through to find references
     *
     * @return array
     */
    public function getTemplateImportDependencies($bodyContent);

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
