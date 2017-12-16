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

    protected $frontMatterEvaluated = false;
    protected $bodyContentEvaluated = false;

    /** @var FrontMatterParser */
    protected $frontMatterParser;

    /** @var array FrontMatter that is read from user documents. */
    protected $frontMatter = [];

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
    public function readContent()
    {
        // $fileStructure[1] is the YAML
        // $fileStructure[2] is the amount of new lines after the closing `---` and the beginning of content
        // $fileStructure[3] is the body of the document
        $fileStructure = array();

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

        // The hard coded 1 is the offset used to count the new line used after the first `---` that is not caught in the regex
        $this->lineOffset = substr_count($fileStructure[1], "\n") + substr_count($fileStructure[2], "\n") + 1;
        $this->bodyContent = $fileStructure[3];

        if (!empty(trim($fileStructure[1])))
        {
            $this->frontMatter = Yaml::parse($fileStructure[1], Yaml::PARSE_DATETIME);

            if (!empty($this->frontMatter) && !is_array($this->frontMatter))
            {
                throw new ParseException('The evaluated FrontMatter should be an array');
            }
        }
        else
        {
            $this->frontMatter = array();
        }

        $this->frontMatterEvaluated = false;
        $this->bodyContentEvaluated = false;
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
            $this->frontMatter = [];
        }
        elseif (!$this->frontMatterEvaluated && $evaluateYaml)
        {
            $this->evaluateYaml($this->frontMatter);
        }

        return $this->frontMatter;
    }

    /**
     * Evaluate the FrontMatter in this object by merging a custom array of data.
     *
     * @param array|null $variables An array of YAML variables to use in evaluating the `$permalink` value
     */
    final public function evaluateFrontMatter(array $variables = null)
    {
        if ($variables !== null)
        {
            $this->frontMatter = array_merge($this->frontMatter, $variables);
            $this->evaluateYaml($this->frontMatter);
        }
    }

    /**
     * Returns true when the evaluated Front Matter has expanded values embeded.
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
    private function evaluateYaml(&$yaml)
    {
        try
        {
            // The second parameter for this parser must match the $specialFrontMatter structure
            $this->frontMatterParser = new FrontMatterParser($yaml, [
                'filePath' => $this->getRelativeFilePath(),
            ]);
            $this->frontMatterParser->parse();
            $this->frontMatterEvaluated = true;
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
