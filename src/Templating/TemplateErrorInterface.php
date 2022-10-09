<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating;

use Throwable;

/**
 * This interface is used to throw exceptions whenever a template cannot be compiled or parsed by the template engine.
 */
interface TemplateErrorInterface extends Throwable
{
    public function getTemplateLine(): int;

    /**
     * @param int $lineNumber
     */
    public function setTemplateLine($lineNumber): self;

    public function getContent(): string;

    /**
     * @param string $content
     */
    public function setContent($content): self;

    public function getName(): string;

    /**
     * @param string $name
     */
    public function setName($name): self;

    public function getRelativeFilePath(): string;

    /**
     * @param string $filePath
     */
    public function setRelativeFilePath($filePath): self;

    public function buildException(): void;
}
