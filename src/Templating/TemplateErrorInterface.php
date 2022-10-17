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

    public function setTemplateLine(int $lineNumber): self;

    public function getContent(): string;

    public function setContent(string $content): self;

    public function getName(): string;

    public function setName(string $name): self;

    public function getRelativeFilePath(): string;

    public function setRelativeFilePath(string $filePath): self;

    public function buildException(): void;
}
