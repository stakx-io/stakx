<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
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
     */
    public function hasProfiler(): bool;

    /**
     * Set a profiler for this bridge.
     *
     * @param null|object $profiler
     */
    public function setProfiler($profiler): void;

    /**
     * Get the output of this profiler that'll be written to the console.
     */
    public function getProfilerOutput(Compiler $compiler): string;

    /**
     * Get all of the references to either DataItems or ContentItems inside of given string.
     *
     * @param string $namespace   'collections' or 'data'
     * @param string $bodyContent The body we're looking through to find references
     */
    public function getAssortmentDependencies($namespace, $bodyContent): array;

    /**
     * Get all of the "import", "from", and "include" dependencies from a template body.
     *
     * @param string $bodyContent The body we're looking through to find references
     */
    public function getTemplateImportDependencies($bodyContent): array;

    /**
     * Set or update a global variable within the template engine.
     *
     * These will be values that are **always** available to all templates that are compiled by stakx.
     *
     * @param string $key
     */
    public function setGlobalVariable($key, mixed $value): void;

    /**
     * Return a template that'll be used to compile templates with data.
     *
     * @param string $templateContent
     *
     * @throws TemplateErrorInterface when an error occurs while preparing/compiling a template for use
     */
    public function createTemplate($templateContent): TemplateInterface;

    /**
     * Clear the internal cache for template engine.
     */
    public function clearTemplateCache(): void;
}
