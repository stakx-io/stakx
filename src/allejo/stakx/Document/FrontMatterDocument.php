<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Exception\FileAwareException;
use allejo\stakx\Exception\InvalidSyntaxException;
use allejo\stakx\FrontMatter\Exception\YamlVariableUndefinedException;
use allejo\stakx\FrontMatter\FrontMatterParser;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

abstract class FrontMatterDocument extends ReadableDocument implements \IteratorAggregate, \ArrayAccess
{
    const TEMPLATE = "---\n%s\n---\n\n%s";

    /** @var array Functions that are white listed and can be called from templates. */
    public static $whiteListedFunctions = [
        'getPermalink', 'getRedirects', 'getTargetFile', 'getContent',
        'getFilename', 'getBasename', 'getExtension', 'isDraft',
    ];

    /** @var array FrontMatter keys that will be defined internally and cannot be overridden by users. */
    protected $specialFrontMatter = [
        'filePath' => null,
    ];

    /** @var bool Whether or not the body content has been evaluated yet. */
    protected $bodyContentEvaluated = false;

    /** @var FrontMatterParser */
    protected $frontMatterParser;

    /** @var array The raw FrontMatter that has not been evaluated. */
    protected $rawFrontMatter = [];

    /** @var array|null FrontMatter that is read from user documents. */
    protected $frontMatter = null;

    /** @var int The number of lines that Twig template errors should offset. */
    protected $lineOffset = 0;

    ///
    // Getter functions
    ///

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return (new \ArrayIterator($this->frontMatter));
    }

    /**
     * Get the number of lines that are taken up by FrontMatter and whitespace.
     *
     * @return int
     */
    public function getLineOffset()
    {
        return $this->lineOffset;
    }

    /**
     * Get whether or not this document is a draft.
     *
     * @return bool
     */
    public function isDraft()
    {
        return (isset($this->frontMatter['draft']) && $this->frontMatter['draft'] === true);
    }

    ///
    // FrontMatter functionality
    ///

    /**
     * {@inheritdoc}
     */
    protected function beforeReadContents()
    {
        if (!$this->file->exists())
        {
            throw new FileNotFoundException(null, 0, null, $this->file->getAbsolutePath());
        }

        // If the "Last Modified" time is equal to what we have on record, then there's no need to read the file again
        if ($this->metadata['last_modified'] === $this->file->getMTime())
        {
            return false;
        }

        $this->metadata['last_modified'] = $this->file->getMTime();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function readContents($readNecessary)
    {
        if (!$readNecessary)
        {
            return [];
        }

        $fileStructure = [];
        $rawFileContents = $this->file->getContents();
        preg_match('/---\R(.*?\R)?---(\s+)(.*)/s', $rawFileContents, $fileStructure);

        if (count($fileStructure) != 4)
        {
            throw new InvalidSyntaxException('Invalid FrontMatter file', 0, null, $this->getRelativeFilePath());
        }

        if (empty(trim($fileStructure[3])))
        {
            throw new InvalidSyntaxException('FrontMatter files must have a body to render', 0, null, $this->getRelativeFilePath());
        }

        return $fileStructure;
    }

    /**
     * {@inheritdoc}
     */
    protected function afterReadContents($fileStructure)
    {
        // The file wasn't modified since our last read, so we can exit out quickly
        if (empty($fileStructure))
        {
            return;
        }

        /*
         * $fileStructure[1] is the YAML
         * $fileStructure[2] is the amount of new lines after the closing `---` and the beginning of content
         * $fileStructure[3] is the body of the document
         */

        // The hard coded 1 is the offset used to count the new line used after the first `---` that is not caught in the regex
        $this->lineOffset = substr_count($fileStructure[1], "\n") + substr_count($fileStructure[2], "\n") + 1;

        //
        // Update the FM of the document, if necessary
        //

        $fmHash = md5($fileStructure[1]);

        if ($this->metadata['fm_hash'] !== $fmHash)
        {
            $this->metadata['fm_hash'] = $fmHash;

            if (!empty(trim($fileStructure[1])))
            {
                $this->rawFrontMatter = Yaml::parse($fileStructure[1], Yaml::PARSE_DATETIME);

                if (!empty($this->rawFrontMatter) && !is_array($this->rawFrontMatter))
                {
                    throw new ParseException('The evaluated FrontMatter should be an array');
                }
            }
            else
            {
                $this->rawFrontMatter = array();
            }
        }

        //
        // Update the body of the document, if necessary
        //

        $bodyHash = md5($fileStructure[3]);

        if ($this->metadata['body_sum'] !== $bodyHash)
        {
            $this->metadata['body_sum'] = $bodyHash;
            $this->bodyContent = $fileStructure[3];
            $this->bodyContentEvaluated = false;
        }
    }

    /**
     * Get the FrontMatter without evaluating its variables or special functionality.
     *
     * @return array
     */
    final public function getRawFrontMatter()
    {
        return $this->rawFrontMatter;
    }

    /**
     * Get the FrontMatter for this document.
     *
     * @param bool $evaluateYaml Whether or not to evaluate any variables.
     *
     * @return array
     */
    final public function getFrontMatter($evaluateYaml = true)
    {
        if ($this->frontMatter === null)
        {
            throw new \LogicException('FrontMatter has not been evaluated yet, be sure FrontMatterDocument::evaluateFrontMatter() is called before.');
        }

        return $this->frontMatter;
    }

    /**
     * {@inheritdoc}
     */
    final public function evaluateFrontMatter(array $variables = [], array $complexVariables = [])
    {
        $this->frontMatter = array_merge($this->rawFrontMatter, $variables);
        $this->evaluateYaml($this->frontMatter, $complexVariables);
    }

    /**
     * Returns true when the evaluated Front Matter has expanded values embedded.
     *
     * @return bool
     */
    final public function hasExpandedFrontMatter()
    {
        return ($this->frontMatterParser !== null && $this->frontMatterParser->hasExpansion());
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
    private function evaluateYaml(array &$yaml, array $complexVariables = [])
    {
        try
        {
            // The second parameter for this parser must match the $specialFrontMatter structure
            $this->frontMatterParser = new FrontMatterParser($yaml, [
                'filePath' => $this->getRelativeFilePath(),
            ]);
            $this->frontMatterParser->addComplexVariables($complexVariables);
            $this->frontMatterParser->parse();
        }
        catch (\Exception $e)
        {
            throw FileAwareException::castException($e, $this->getRelativeFilePath());
        }
    }

    ///
    // ArrayAccess Implementation
    ///

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('FrontMatter is read-only.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        if (isset($this->frontMatter[$offset]) || isset($this->specialFrontMatter[$offset]))
        {
            return true;
        }

        $fxnCall = 'get' . ucfirst($offset);

        return method_exists($this, $fxnCall) && in_array($fxnCall, static::$whiteListedFunctions);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('FrontMatter is read-only.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (isset($this->specialFrontMatter[$offset]))
        {
            return $this->specialFrontMatter[$offset];
        }

        $fxnCall = 'get' . ucfirst($offset);

        if (in_array($fxnCall, self::$whiteListedFunctions) && method_exists($this, $fxnCall))
        {
            return call_user_func_array([$this, $fxnCall], []);
        }

        if (isset($this->frontMatter[$offset]))
        {
            return $this->frontMatter[$offset];
        }

        return null;
    }
}
