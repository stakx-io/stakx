<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Exception\FileAwareException;
use allejo\stakx\Exception\InvalidSyntaxException;
use allejo\stakx\FrontMatter\Exception\YamlVariableUndefinedException;
use allejo\stakx\FrontMatter\FrontMatterParser;
use ArrayAccess;
use ArrayIterator;
use Exception;
use IteratorAggregate;
use LogicException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

abstract class FrontMatterDocument extends ReadableDocument implements IteratorAggregate, ArrayAccess
{
    public const TEMPLATE = "---\n%s\n---\n\n%s";

    /** @var array Functions that are white listed and can be called from templates. */
    public static array $whiteListedFunctions = [
        'getPermalink', 'getRedirects', 'getTargetFile', 'getContent',
        'getFilename', 'getBasename', 'getExtension', 'isDraft',
    ];

    /** @var array FrontMatter keys that will be defined internally and cannot be overridden by users. */
    protected array $specialFrontMatter = [
        'filePath' => null,
    ];

    /** @var bool Whether or not the body content has been evaluated yet. */
    protected bool $bodyContentEvaluated = false;

    protected FrontMatterParser $frontMatterParser;

    /** @var array The raw FrontMatter that has not been evaluated. */
    protected array $rawFrontMatter = [];

    /** @var null|array FrontMatter that is read from user documents. */
    protected ?array $frontMatter = null;

    /** @var int The number of lines that Twig template errors should offset. */
    protected int $lineOffset = 0;

    //
    // Getter functions
    //

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new ArrayIterator($this->frontMatter);
    }

    /**
     * Get the number of lines that are taken up by FrontMatter and whitespace.
     */
    public function getLineOffset(): int
    {
        return $this->lineOffset;
    }

    /**
     * Get whether or not this document is a draft.
     */
    public function isDraft(): bool
    {
        return isset($this->frontMatter['draft']) && $this->frontMatter['draft'] === true;
    }

    /**
     * Get the FrontMatter without evaluating its variables or special functionality.
     */
    final public function getRawFrontMatter(): array
    {
        return $this->rawFrontMatter;
    }

    /**
     * Get the FrontMatter for this document.
     *
     * @param bool $evaluateYaml whether or not to evaluate any variables
     */
    final public function getFrontMatter($evaluateYaml = true): array
    {
        if ($this->frontMatter === null) {
            throw new LogicException('FrontMatter has not been evaluated yet, be sure FrontMatterDocument::evaluateFrontMatter() is called before.');
        }

        return $this->frontMatter;
    }

    /**
     * {@inheritdoc}
     */
    final public function evaluateFrontMatter(array $variables = [], array $complexVariables = []): void
    {
        $this->frontMatter = array_merge($this->rawFrontMatter, $variables);
        $this->evaluateYaml($this->frontMatter, $complexVariables);
    }

    /**
     * Returns true when the evaluated Front Matter has expanded values embedded.
     */
    final public function hasExpandedFrontMatter(): bool
    {
        return $this->frontMatterParser !== null && $this->frontMatterParser->hasExpansion();
    }

    //
    // ArrayAccess Implementation
    //

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new LogicException('FrontMatter is read-only.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        if (isset($this->frontMatter[$offset]) || isset($this->specialFrontMatter[$offset])) {
            return true;
        }

        $fxnCall = 'get' . ucfirst((string)$offset);

        return method_exists($this, $fxnCall) && in_array($fxnCall, static::$whiteListedFunctions);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new LogicException('FrontMatter is read-only.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): mixed
    {
        if (isset($this->specialFrontMatter[$offset])) {
            return $this->specialFrontMatter[$offset];
        }

        $fxnCall = 'get' . ucfirst((string)$offset);

        if (in_array($fxnCall, self::$whiteListedFunctions) && method_exists($this, $fxnCall)) {
            return call_user_func_array([$this, $fxnCall], []);
        }

        if (isset($this->frontMatter[$offset])) {
            return $this->frontMatter[$offset];
        }

        return null;
    }

    //
    // FrontMatter functionality
    //

    /**
     * {@inheritdoc}
     */
    protected function beforeReadContents(): mixed
    {
        if (!$this->file->exists()) {
            throw new FileNotFoundException(null, 0, null, $this->file->getAbsolutePath());
        }

        // If the "Last Modified" time is equal to what we have on record, then there's no need to read the file again
        if ($this->metadata['last_modified'] === $this->file->getMTime()) {
            return false;
        }

        $this->metadata['last_modified'] = $this->file->getMTime();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function readContents($readNecessary): mixed
    {
        if (!$readNecessary) {
            return [];
        }

        $fileStructure = [];
        $rawFileContents = $this->file->getContents();
        preg_match('/---\R(.*?\R)?---(\s+)(.*)/s', (string)$rawFileContents, $fileStructure);

        if (count($fileStructure) != 4) {
            throw new InvalidSyntaxException('Invalid FrontMatter file', 0, null, $this->getRelativeFilePath());
        }

        if (empty(trim((string)$fileStructure[3]))) {
            throw new InvalidSyntaxException('FrontMatter files must have a body to render', 0, null, $this->getRelativeFilePath());
        }

        return $fileStructure;
    }

    /**
     * {@inheritdoc}
     */
    protected function afterReadContents($fileStructure): void
    {
        // The file wasn't modified since our last read, so we can exit out quickly
        if (empty($fileStructure)) {
            return;
        }

        /*
         * $fileStructure[1] is the YAML
         * $fileStructure[2] is the amount of new lines after the closing `---` and the beginning of content
         * $fileStructure[3] is the body of the document
         */

        // The hard coded 1 is the offset used to count the new line used after the first `---` that is not caught in the regex
        $this->lineOffset = substr_count((string)$fileStructure[1], "\n") + substr_count((string)$fileStructure[2], "\n") + 1;

        //
        // Update the FM of the document, if necessary
        //

        $fmHash = md5((string)$fileStructure[1]);

        if ($this->metadata['fm_hash'] !== $fmHash) {
            $this->metadata['fm_hash'] = $fmHash;

            if (!empty(trim((string)$fileStructure[1]))) {
                $this->rawFrontMatter = Yaml::parse($fileStructure[1], Yaml::PARSE_DATETIME);

                if (!empty($this->rawFrontMatter) && !is_array($this->rawFrontMatter)) {
                    throw new ParseException('The evaluated FrontMatter should be an array');
                }
            } else {
                $this->rawFrontMatter = [];
            }
        }

        //
        // Update the body of the document, if necessary
        //

        $bodyHash = md5((string)$fileStructure[3]);

        if ($this->metadata['body_sum'] !== $bodyHash) {
            $this->metadata['body_sum'] = $bodyHash;
            $this->bodyContent = $fileStructure[3];
            $this->bodyContentEvaluated = false;
        }
    }

    /**
     * Evaluate an array of data for FrontMatter variables. This function will modify the array in place.
     *
     * @param array $yaml An array of data containing FrontMatter variables
     *
     * @see $specialFrontMatter
     *
     * @throws YamlVariableUndefinedException A FrontMatter variable used does not exist
     */
    private function evaluateYaml(array &$yaml, array $complexVariables = []): void
    {
        try {
            // The second parameter for this parser must match the $specialFrontMatter structure
            $this->frontMatterParser = new FrontMatterParser($yaml, [
                'basename' => $this->getBasename(),
                'filename' => $this->getFilename(),
                'filePath' => $this->getRelativeFilePath(),
            ]);
            $this->frontMatterParser->addComplexVariables($complexVariables);
            $this->frontMatterParser->parse();
        } catch (Exception $e) {
            throw FileAwareException::castException($e, $this->getRelativeFilePath());
        }
    }
}
