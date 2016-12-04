<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Engines;

use allejo\stakx\Exception\YamlUnsupportedVariableException;
use allejo\stakx\Exception\YamlVariableUndefinedException;
use allejo\stakx\Utilities\ArrayUtilities;

class FrontMatterParser
{
    /**
     * The RegEx used to identify Front Matter variables
     */
    const VARIABLE_DEF = '/(?<!\\\\)%([a-zA-Z]+)/';

    /**
     * A list of special fields in the Front Matter that will support expansion
     *
     * @var string[]
     */
    private static $expandableFields = array('permalink');

    /**
     * Whether or not an field was expanded into several values
     *
     * Only fields specified in $expandableFields will cause this value to be set to true
     *
     * @var bool
     */
    private $expansionUsed;

    /**
     * The current depth of the recursion for evaluating nested arrays in the Front Matter
     *
     * @var int
     */
    private $nestingLevel;

    /**
     * The current hierarchy of the keys that are being evaluated
     *
     * Since arrays can be nested, we'll keep track of the keys up until the current depth. This information is used for
     * error reporting
     *
     * @var array
     */
    private $yamlKeys;

    /**
     * The entire Front Matter block; evaluation will happen in place
     *
     * @var array
     */
    private $frontMatter;

    /**
     * FrontMatterParser constructor
     *
     * @param array $rawFrontMatter
     */
    public function __construct(&$rawFrontMatter)
    {
        $this->expansionUsed = false;
        $this->nestingLevel = 0;
        $this->yamlKeys = array();

        $this->frontMatter = &$rawFrontMatter;

        $this->evaluateBlock($this->frontMatter);
    }

    /**
     * True if any fields were expanded in the Front Matter block
     *
     * @return bool
     */
    public function hasExpansion ()
    {
        return $this->expansionUsed;
    }

    /**
     * Evaluate an array as Front Matter
     *
     * @param array $yaml
     */
    private function evaluateBlock (&$yaml)
    {
        $this->nestingLevel++;

        foreach ($yaml as $key => &$value)
        {
            $this->yamlKeys[$this->nestingLevel] = $key;
            $keys = implode('.', $this->yamlKeys);

            if (in_array($key, self::$expandableFields, true))
            {
                $value = $this->evaluateExpandableField($keys, $value);
            }
            else if (is_array($value))
            {
                $this->evaluateBlock($value);
            }
            else
            {
                $value = $this->evaluateBasicType($keys, $value);
            }
        }

        $this->nestingLevel--;
        $this->yamlKeys = array();
    }

    /**
     * Evaluate an expandable field
     *
     * @param  string $key
     * @param  string $fmStatement
     *
     * @return array
     */
    private function evaluateExpandableField ($key, $fmStatement)
    {
        if (!is_array($fmStatement))
        {
            $fmStatement = array($fmStatement);
        }

        $wip = array();

        foreach ($fmStatement as $statement)
        {
            $value = $this->evaluateBasicType($key, $statement, true);

            // Only continue expanding a Front Matter value if variables still exist
            $expandingVars = $this->getFrontMatterVariables($value);
            if (!empty($expandingVars))
            {
                $value = $this->evaluateArrayType($key, $value, $expandingVars);
            }

            $wip[] = $value;
        }

        return $wip;
    }

    /**
     * Convert a string or an array into an array of values created through "value expansion"
     *
     * @param  string $key
     * @param  string $fmStatement
     * @param  array  $variables
     *
     * @return array
     *
     * @throws YamlUnsupportedVariableException If a multidimensional array is given for value expansion
     */
    private function evaluateArrayType ($key, $fmStatement, $variables)
    {
        if (!is_array($fmStatement))
        {
            $fmStatement = array($fmStatement);
        }

        foreach ($variables as $variable)
        {
            if (ArrayUtilities::is_multidimensional($this->frontMatter[$variable]))
            {
                throw new YamlUnsupportedVariableException("Yaml array expansion is not supported with multidimensional arrays with `$variable` for key `$key`");
            }

            $this->expansionUsed = true;
            $wip = array();

            foreach ($fmStatement as &$statement)
            {
                if (!is_array($statement))
                {
                    $stringValue = $statement;
                    $statement = array(
                        'iterator' => array()
                    );
                    $statement['evaluated'] = $stringValue;
                }

                foreach ($this->frontMatter[$variable] as $value)
                {
                    $wip[] = array(
                        'evaluated' => str_replace('%' . $variable, $value, $statement['evaluated']),
                        'iterator'  => array_merge($statement['iterator'], array($variable => $value))
                    );
                }
            }

            $fmStatement = $wip;
        }

        return $fmStatement;
    }

    /**
     * Evaluate an string for FrontMatter variables and replace them with the corresponding values
     *
     * @param  string $key          The key of the Front Matter value
     * @param  string $string       The string that will be evaluated
     * @param  bool   $ignoreArrays When set to true, an exception won't be thrown when an array is found with the
     *                              interpolation
     *
     * @return string The final string with variables evaluated
     *
     * @throws YamlUnsupportedVariableException A FrontMatter variable is not an int, float, or string
     */
    private function evaluateBasicType ($key, $string, $ignoreArrays = false)
    {
        $variables = $this->getFrontMatterVariables($string);

        foreach ($variables as $variable)
        {
            $value = $this->getVariableValue($key, $variable);

            if (is_array($value) || is_bool($value))
            {
                if ($ignoreArrays) { continue; }

                throw new YamlUnsupportedVariableException("Yaml variable `$variable` for `$key` is not a supported data type.");
            }

            $string = str_replace('%' . $variable, $value, $string);
        }

        return $string;
    }

    /**
     * Get an array of FrontMatter variables in the specified string that need to be interpolated
     *
     * @param  string $string
     *
     * @return string[]
     */
    private function getFrontMatterVariables ($string)
    {
        $variables = array();

        preg_match_all(self::VARIABLE_DEF, $string, $variables);

        // Default behavior causes $variables[0] is the entire string that was matched. $variables[1] will be each
        // matching result individually.
        return $variables[1];
    }

    /**
     * Get the value of a FM variable or throw an exception
     *
     * @param  string $key
     * @param  string $varName
     *
     * @return mixed
     * @throws YamlVariableUndefinedException
     */
    private function getVariableValue ($key, $varName)
    {
        if (!isset($this->frontMatter[$varName]))
        {
            throw new YamlVariableUndefinedException("Yaml variable `$varName` is not defined for: $key");
        }

        return $this->frontMatter[$varName];
    }
}