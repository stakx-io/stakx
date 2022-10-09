<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Templating\TemplateBridgeInterface;
use allejo\stakx\Templating\TemplateErrorInterface;

trait TemplateEngineDependent
{
    protected $importDependencies = [];

    protected $dataDependencies = [];

    protected ?TemplateBridgeInterface $templateEngine = null;

    /**
     * Set the template engine used to parse the body of the document.
     *
     * @return $this
     */
    public function setTemplateEngine(TemplateBridgeInterface $templateEngine)
    {
        $this->templateEngine = $templateEngine;

        return $this;
    }

    /**
     * Check whether this object has a reference to a collection or data item.
     *
     * @param string $namespace 'collections' or 'data'
     * @param string $needle
     */
    public function hasDependencyOnCollection($namespace, $needle): bool
    {
        return
            in_array($needle, $this->dataDependencies[$namespace])
            || (is_null($needle) && !empty($this->dataDependencies[$namespace]));
    }

    /**
     * Check whether this object has an "import" or "from" reference to a given path.
     *
     * @param string $filePath
     */
    public function hasDependencyOnTemplateImport($filePath): bool
    {
        return in_array($filePath, $this->importDependencies);
    }

    /**
     * @return string[]
     */
    public function getImportDependencies(): array
    {
        return $this->importDependencies;
    }

    /**
     * @return string[]
     */
    public function getCollectionDependencies(): array
    {
        return $this->dataDependencies;
    }

    /**
     * Pass the document's body through the template engine.
     *
     * @param string $bodyContent
     *
     * @throws TemplateErrorInterface
     */
    protected function parseTemplateLanguage($bodyContent): string
    {
        if ($this->templateEngine !== null) {
            $this->importDependencies = $this->templateEngine->getTemplateImportDependencies($bodyContent);
            $this->dataDependencies = [
                'collections' => $this->templateEngine->getAssortmentDependencies('collections', $bodyContent),
                'data' => $this->templateEngine->getAssortmentDependencies('data', $bodyContent),
            ];

            $template = $this->templateEngine->createTemplate($bodyContent);

            return $template->render();
        }

        return $bodyContent;
    }
}
