<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Exception\DependencyMissingException;
use allejo\stakx\Exception\UnsupportedDataTypeException;
use allejo\stakx\FrontMatter\Parser;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

class DataItem extends PermalinkDocument implements
    \ArrayAccess,
    \IteratorAggregate,
    TwigDocument
{
    protected $data;

    private $namespace;
    private $pageView;

    public function __construct($filePath)
    {
        $this->namespace = '';

        parent::__construct($filePath);
    }

    public function getData()
    {
        return $this->data;
    }

    public function getName()
    {
        return $this->getBaseName();
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateFrontMatter($variables = array())
    {
        $workspace = array_merge($this->data, $variables);
        $parser = new Parser($workspace, array(
            'filename' => $this->getFileName(),
            'basename' => $this->getBaseName(),
        ));

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
    protected function buildPermalink()
    {
        return;
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
    public function setPageView(&$pageView)
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
    public function refreshFileContent()
    {
        // This function can be called after the initial object was created and the file may have been deleted since the
        // creation of the object.
        if (!$this->fs->exists($this->filePath))
        {
            throw new FileNotFoundException(null, 0, null, $this->filePath);
        }

        $content = file_get_contents($this->getFilePath());
        $fxnName = 'from' . ucfirst($this->getExtension());

        if (method_exists(get_called_class(), $fxnName))
        {
            $this->handleDependencies($this->getExtension());
            $this->data = (null !== ($c = $this->$fxnName($content))) ? $c : array();

            return;
        }

        throw new UnsupportedDataTypeException($this->getExtension(), 'There is no support to handle this file extension.');
    }

    ///
    // File parsing helpers
    ///

    /**
     * Convert from CSV into an associative array.
     *
     * @param string $content CSV formatted text
     *
     * @return array
     */
    private function fromCsv($content)
    {
        $rows = array_map('str_getcsv', explode("\n", trim($content)));
        $columns = array_shift($rows);
        $csv = array();

        foreach ($rows as $row)
        {
            $csv[] = array_combine($columns, $row);
        }

        return $csv;
    }

    /**
     * Convert from JSON into an associative array.
     *
     * @param string $content JSON formatted text
     *
     * @return array
     */
    private function fromJson($content)
    {
        return json_decode($content, true);
    }

    /**
     * Convert from XML into an associative array.
     *
     * @param string $content XML formatted text
     *
     * @return array
     */
    private function fromXml($content)
    {
        return json_decode(json_encode(simplexml_load_string($content)), true);
    }

    /**
     * Convert from YAML into an associative array.
     *
     * @param string $content YAML formatted text
     *
     * @return array
     */
    private function fromYaml($content)
    {
        return Yaml::parse($content, Yaml::PARSE_DATETIME);
    }

    /**
     * An alias for handling `*.yml` files.
     *
     * @param string $content YAML formatted text
     *
     * @return array
     */
    private function fromYml($content)
    {
        return $this->fromYaml($content);
    }

    /**
     * Check for any dependencies needed to parse for a specific file extension
     *
     * @param string $extension
     *
     * @todo 0.1.0 Create a help page on the main stakx website for this topic and link to it
     *
     * @throws DependencyMissingException
     */
    private function handleDependencies($extension)
    {
        if ($extension === 'xml' && !function_exists('simplexml_load_string'))
        {
            throw new DependencyMissingException('XML', 'XML support is not available with the current PHP installation.');
        }
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
            'getExtension', 'getFilePath', 'getName', 'getRelativeFilePath'
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
