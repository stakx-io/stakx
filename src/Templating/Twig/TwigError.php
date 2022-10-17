<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use allejo\stakx\Templating\TemplateErrorInterface;
use Exception;
use Twig\Error\Error;
use Twig\Source;

class TwigError extends Exception implements TemplateErrorInterface
{
    private string $content;

    private string $relativeFilePath;

    private string $name;

    public function __construct(private readonly Error $error)
    {
        parent::__construct($error->getRawMessage());
    }

    public function getTemplateLine(): int
    {
        return $this->error->getTemplateLine();
    }

    public function setTemplateLine(int $lineNumber): self
    {
        $this->error->setTemplateLine($lineNumber);

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getRelativeFilePath(): string
    {
        return $this->relativeFilePath;
    }

    public function setRelativeFilePath(string $filePath): self
    {
        $this->relativeFilePath = $filePath;

        return $this;
    }

    public function buildException(): void
    {
        $this->error->setSourceContext(new Source(
            $this->content,
            $this->name,
            $this->relativeFilePath
        ));
    }
}
