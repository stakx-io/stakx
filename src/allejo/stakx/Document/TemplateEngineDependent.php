<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Templating\TemplateBridgeInterface;
use allejo\stakx\Templating\TemplateErrorInterface;

trait TemplateEngineDependent
{
    protected $importDependencies = [];

    protected $dataDependencies = [];

    /** @var TemplateBridgeInterface */
    protected $templateEngine;

    /**
     * Set the template engine used to parse the body of the document.
     *
     * @param TemplateBridgeInterface $templateEngine
     *
     * @return $this
     */
    public function setTemplateEngine(TemplateBridgeInterface $templateEngine)
    {
        $this->templateEngine = $templateEngine;

        return $this;
    }

    /**
     * Pass the document's body through the template engine.
     *
     * @param string $bodyContent
     *
     * @throws TemplateErrorInterface
     *
     * @return string
     */
    protected function parseTemplateLanguage($bodyContent)
    {
        if ($this->templateEngine !== null)
        {
            $this->importDependencies = $this->templateEngine->getTemplateImportDependencies($bodyContent);
            $this->dataDependencies = [
                'collections' => $this->templateEngine->getAssortmentDependencies('collections', $bodyContent),
                'data'        => $this->templateEngine->getAssortmentDependencies('data', $bodyContent),
            ];

            $template = $this->templateEngine->createTemplate($bodyContent);

            return $template->render();
        }

        return $bodyContent;
    }

    /**
     * Check whether this object has a reference to a collection or data item.
     *
     * @param string $namespace 'collections' or 'data'
     * @param string $needle
     *
     * @return bool
     */
    public function hasDependencyOnAssortment($namespace, $needle)
    {
        return
            in_array($needle, $this->dataDependencies[$namespace]) ||
            (is_null($needle) && !empty($this->dataDependencies[$namespace]));
    }

    /**
     * Check whether this object has an "import" or "from" reference to a given path.
     *
     * @param string $filePath
     *
     * @return bool
     */
    public function hasDependencyOnTemplateImport($filePath)
    {
        return (in_array($filePath, $this->importDependencies));
    }

    /**
     * @return string[]
     */
    public function getImportDependencies()
    {
        return $this->importDependencies;
    }

    public function getAssortmentDependencies()
    {
        return $this->dataDependencies;
    }
}
