<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\DataTransformer\DataTransformerInterface;
use allejo\stakx\DataTransformer\DataTransformerManager;
use allejo\stakx\Filesystem\File;
use allejo\stakx\FrontMatter\FrontMatterParser;
use ArrayIterator;
use LogicException;

class DataItem extends ReadableDocument implements CollectableItem, TemplateReadyDocument, PermalinkDocument
{
    use CollectableItemTrait;
    use PermalinkDocumentTrait;

    protected DataTransformerInterface $dataTransformer;

    protected array $frontMatter;

    protected array $data;

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
    public function setDataTransformer(DataTransformerManager $manager): void
    {
        $this->dataTransformer = $manager->getTransformer($this->getExtension());
        $this->readContent();
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateFrontMatter(array $variables = [], array $complexVariables = []): void
    {
        $this->frontMatter = array_merge($this->data, $variables);
        $parser = new FrontMatterParser($this->frontMatter, [
            'basename' => $this->getBasename(),
            'filename' => $this->getFileName(),
            'filePath' => $this->getRelativeFilePath(),
        ]);
        $parser->addComplexVariables($complexVariables);
        $parser->parse();

        if (!is_null($parser) && $parser->hasExpansion()) {
            throw new LogicException('The permalink for this item has not been set.');
        }

        $permalink = $this->frontMatter['permalink'];

        if (is_array($permalink)) {
            $this->permalink = $permalink[0];
            array_shift($permalink);
            $this->redirects = $permalink;
        } else {
            $this->permalink = $permalink;
            $this->redirects = [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildPermalink($force = false): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function readContents($mixed): mixed
    {
        $content = $this->file->getContents();
        $this->data = $this->dataTransformer::transformData($content);

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): mixed
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function isDraft(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function createJail(): JailedDocument
    {
        $whiteListedFunctions = array_merge(FrontMatterDocument::$whiteListedFunctions, [
        ]);

        $jailedFunctions = [
            'getDataset' => 'getNamespace',
        ];

        return new JailedDocument($this, $whiteListedFunctions, $jailedFunctions);
    }

    //
    // JsonSerializable implementation
    //

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }

    //
    // IteratorAggregate implementation
    //

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new ArrayIterator($this->data);
    }

    //
    // ArrayAccess implementation
    //

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]) || isset($this->frontMatter[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): mixed
    {
        $fxnCall = 'get' . ucfirst((string)$offset);

        if (in_array($fxnCall, FrontMatterDocument::$whiteListedFunctions) && method_exists($this, $fxnCall)) {
            return call_user_func_array([$this, $fxnCall], []);
        }

        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }

        if (isset($this->frontMatter[$offset])) {
            return $this->frontMatter[$offset];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new LogicException('DataItems are read-only.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new LogicException('DataItems are read-only.');
    }
}
