<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating;

/**
 * This interface is used to throw exceptions whenever a template cannot be compiled or parsed by the template engine.
 */
interface TemplateErrorInterface extends \Throwable
{
    /**
     * @return int
     */
    public function getTemplateLine();

    /**
     * @param int $lineNumber
     *
     * @return self
     */
    public function setTemplateLine($lineNumber);

    /**
     * @param string $content
     *
     * @return self
     */
    public function setContent($content);

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName($name);

    /**
     * @param string $filePath
     *
     * @return self
     */
    public function setRelativeFilePath($filePath);

    /**
     * @return void
     */
    public function buildException();
}
