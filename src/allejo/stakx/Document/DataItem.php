<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\DataTransformer\DataTransformerInterface;
use allejo\stakx\DataTransformer\DataTransformerManager;
use allejo\stakx\Filesystem\File;
use allejo\stakx\FrontMatter\FrontMatterParser;

class DataItem extends ReadableDocument implements CollectableItem, TemplateReadyDocument, PermalinkDocument
{
    use CollectableItemTrait;
    use PermalinkDocumentTrait;

    /** @var array */
    protected $data;

    /** @var DataTransformerInterface */
    protected $dataTransformer;

    /**
     * DataItem constructor.
     */
    public function __construct(File $file)
    {
        $this->noReadOnConstructor = true;

        parent::__construct($file);
    }

    /**
     * Set the transformer used to convert the file contents into an array,.
     */
    public function setDataTransformer(DataTransformerManager $manager)
    {
        $this->dataTransformer = $manager->getTransformer($this->getExtension());
        $this->readContent();
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateFrontMatter(array $variables = [], array $complexVariables = [])
    {
        $workspace = array_merge($this->data, $variables);
        $parser = new FrontMatterParser($workspace, [
            'filename' => $this->getFileName(),
            'basename' => $this->getBaseName(),
        ]);
        $parser->addComplexVariables($complexVariables);
        $parser->parse();

        if (!is_null($parser) && $parser->hasExpansion())
        {
            throw new \LogicException('The permalink for this item has not been set.');
        }

        $permalink = $workspace['permalink'];

        if (is_array($permalink))
        {
            $this->permalink = $permalink[0];
            array_shift($permalink);
            $this->redirects = $permalink;
        }
        else
        {
            $this->permalink = $permalink;
            $this->redirects = [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildPermalink($force = false)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function readContents($mixed)
    {
        $content = $this->file->getContents();
        $this->data = $this->dataTransformer->transformData($content);
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function isDraft()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function createJail()
    {
        $whiteListedFunctions = array_merge(FrontMatterDocument::$whiteListedFunctions, [
        ]);

        $jailedFunctions = [
            'getDataset' => 'getNamespace',
        ];

        return new JailedDocument($this, $whiteListedFunctions, $jailedFunctions);
    }

    ///
    // JsonSerializable implementation
    ///

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    ///
    // IteratorAggregate implementation
    ///

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    ///
    // ArrayAccess implementation
    ///

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $fxnCall = 'get' . ucfirst($offset);

        if (in_array($fxnCall, FrontMatterDocument::$whiteListedFunctions) && method_exists($this, $fxnCall))
        {
            return call_user_func_array([$this, $fxnCall], []);
        }

        if (isset($this->data[$offset]))
        {
            return $this->data[$offset];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('DataItems are read-only.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('DataItems are read-only.');
    }
}
