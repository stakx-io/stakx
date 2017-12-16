<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DocumentDeprecated;

use allejo\stakx\DataTransformer\DataTransformerInterface;
use allejo\stakx\DataTransformer\DataTransformerManager;
use allejo\stakx\Document\JailedDocument;
use allejo\stakx\FrontMatter\FrontMatterParser;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class DataItem extends PermalinkDocument implements
    RepeatableItem,
    TrackableDocument,
    TwigDocument
{
    /** @var array */
    protected $data;

    /** @var string */
    private $namespace;

    /** @var PageView */
    private $pageView;

    /** @var DataTransformerInterface */
    private $transformer;

    /**
     * DataItem constructor.
     */
    public function __construct($filePath)
    {
        $this->namespace = '';
        $this->noReadOnConstructor = true;

        parent::__construct($filePath);
    }

    public function getData()
    {
        return $this->data;
    }

    public function getObjectName()
    {
        return $this->getBaseName();
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateFrontMatter($variables = array())
    {
        $workspace = array_merge($this->data, $variables);
        $parser = new FrontMatterParser($workspace, array(
            'filename' => $this->getFileName(),
            'basename' => $this->getBaseName(),
        ));
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
            $this->redirects = array();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildPermalink($force = false)
    {
        return;
    }

    /**
     * Set the transformer used to convert the file contents into an array,
     */
    public function setDataTransformer(DataTransformerManager $manager)
    {
        $this->transformer = $manager->getTransformer($this->getExtension());
        $this->readContent();
    }

    ///
    // Twig Document implementation
    ///

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function setParentPageView(PageView &$pageView)
    {
        $this->pageView = &$pageView;
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
    public function readContent()
    {
        // This function can be called after the initial object was created and the file may have been deleted since the
        // creation of the object.
        if (!$this->fs->exists($this->filePath))
        {
            throw new FileNotFoundException(null, 0, null, $this->filePath);
        }

        $content = file_get_contents($this->getAbsoluteFilePath());
        $this->data = $this->transformer->transformData($content);
    }

    ///
    // Jailed Document implementation
    ///

    /**
     * {@inheritdoc}
     */
    public function createJail()
    {
        return new JailedDocument($this, array(
            'getExtension', 'getFilePath', 'getRelativeFilePath'
        ), array(
            'getName' => 'getObjectName'
        ));
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
        return $this->data[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}
