<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\FrontMatter;

use __;
use allejo\stakx\FrontMatter\Exception\YamlUnsupportedVariableException;
use allejo\stakx\FrontMatter\Exception\YamlVariableUndefinedException;
use allejo\stakx\Utilities\ArrayUtilities;

/**
 * The parser tasked with sanitizing and evaluating Front Matter of its variables.
 *
 * stakx's Front Matter has support for "primitive" and "complex" variables. Primitive variables are variables defined
 * through the Front Matter itself whereas complex variables are injected from other sources (e.g. the site's main
 * _config.yml file).
 *
 * ## Primitive Variables
 *
 * **Syntax**: `%variableName`
 *
 * **Example**
 *
 * ```yaml
 * ---
 * variableName: Hello
 * output: %variableName world! # "Hello world!"
 * ---
 * ```
 *
 * ## Complex Variables
 *
 * **Syntax**: `%{site.section.value}`
 *
 * **Example**
 *
 * ```yaml
 * # _config.yml
 *
 * title: stakx's site
 * someParent:
 *   nestedValue: Toast
 * ```
 *
 * ```yaml
 * ---
 * output: Hello from, %{site.title} # "Hello from, stakx's site"
 * title: I want %{site.someParent.nestedValue} # "I want Toast"
 * ---
 * ```
 *
 * @since 0.2.0 Add support for complex variables.
 * @since 0.1.0
 */
class FrontMatterParser
{
    /**
     * The RegEx used to identify Front Matter variables.
     */
    const VARIABLE_DEF = '/(?<!\\\\)%([a-zA-Z]+)/';

    /**
     * The RegEx used to identify special variables.
     */
    const ARRAY_DEF = '/(?<!\\\\)%{([a-zA-Z\.]+)}/';

    /**
     * A list of special fields in the Front Matter that will support expansion.
     *
     * @var string[]
     */
    private static $expandableFields = ['permalink'];

    /**
     * Whether or not an field was expanded into several values.
     *
     * Only fields specified in $expandableFields will cause this value to be set to true
     *
     * @var bool
     */
    private $expansionUsed;

    /**
     * The current depth of the recursion for evaluating nested arrays in the Front Matter.
     *
     * @var int
     */
    private $nestingLevel;

    /**
     * Special FrontMatter keys that are defined manually.
     *
     * @var array
     */
    private $specialKeys;

    /**
     * The current hierarchy of the keys that are being evaluated.
     *
     * Since arrays can be nested, we'll keep track of the keys up until the current depth. This information is used for
     * error reporting
     *
     * @var array
     */
    private $yamlKeys;

    /**
     * The entire Front Matter block; evaluation will happen in place.
     *
     * @var array
     */
    private $frontMatter;

    /**
     * YAML data that is being imported from external sources.
     *
     * @var array
     */
    private $complexVariables;

    /**
     * @param array $rawFrontMatter The array representation of a document's Front Matter
     * @param array $specialKeys    front Matter variables defined manually, which will override any values defined
     *                              through Front Matter
     */
    public function __construct(array &$rawFrontMatter, array $specialKeys = [])
    {
        $this->expansionUsed = false;
        $this->nestingLevel = 0;
        $this->specialKeys = $specialKeys;
        $this->yamlKeys = [];

        $this->frontMatter = &$rawFrontMatter;
        $this->complexVariables = [];
    }

    /**
     * Make complex variables available to the parser.
     *
     * @param array $yaml
     */
    public function addComplexVariables(array $yaml)
    {
        $this->complexVariables = array_merge($this->complexVariables, $yaml);
    }

    /**
     * Trigger the parsing functionality. The given array will be evaluated in place.
     */
    public function parse()
    {
        $this->handleSpecialFrontMatter();
        $this->evaluateBlock($this->frontMatter);
    }

    /**
     * True if any fields were expanded in the FrontMatter block.
     *
     * @return bool
     */
    public function hasExpansion()
    {
        return $this->expansionUsed;
    }

    //
    // Special FrontMatter fields
    //

    /**
     * Special treatment for some FrontMatter variables.
     */
    private function handleSpecialFrontMatter()
    {
        $this->handleSpecialKeys();
        $this->handleDateField();
    }

    /**
     * Merge in the special keys with the existing FrontMatter.
     */
    private function handleSpecialKeys()
    {
        $this->frontMatter = array_merge($this->frontMatter, $this->specialKeys);
    }

    /**
     * Special treatment for the `date` field in FrontMatter that creates three new variables: year, month, day.
     */
    private function handleDateField()
    {
        if (!isset($this->frontMatter['date']))
        {
            return;
        }

        $date = &$this->frontMatter['date'];
        $itemDate = $this->guessDateTime($date);

        if (!$itemDate === false)
        {
            $this->frontMatter['date'] = $itemDate->format('U');
            $this->frontMatter['year'] = $itemDate->format('Y');
            $this->frontMatter['month'] = $itemDate->format('m');
            $this->frontMatter['day'] = $itemDate->format('d');
        }
    }

    //
    // Evaluation
    //

    /**
     * Evaluate an array as Front Matter.
     *
     * @param array $yaml
     */
    private function evaluateBlock(&$yaml)
    {
        ++$this->nestingLevel;

        foreach ($yaml as $key => &$value)
        {
            $this->yamlKeys[$this->nestingLevel] = $key;
            $keys = implode('.', $this->yamlKeys);

            if (in_array($key, self::$expandableFields, true))
            {
                $value = $this->evaluateExpandableField($keys, $value);
            }
            elseif (is_array($value))
            {
                $this->evaluateBlock($value);
            }
            elseif (is_string($value))
            {
                $value = $this->evaluateBasicType($keys, $value);
            }
            elseif ($value instanceof \DateTime)
            {
                $value = $this->castDateTimeTimezone($value->format('U'));
            }
        }

        --$this->nestingLevel;
        $this->yamlKeys = [];
    }

    /**
     * Evaluate an expandable field.
     *
     * @param string $key
     * @param string $fmStatement
     *
     * @return array
     */
    private function evaluateExpandableField($key, $fmStatement)
    {
        if (!is_array($fmStatement))
        {
            $fmStatement = [$fmStatement];
        }

        $wip = [];

        foreach ($fmStatement as $statement)
        {
            $value = $this->evaluateBasicType($key, $statement, true);

            // Only continue expansion if there are Front Matter variables remain in the string, this means there'll be
            // Front Matter variables referencing arrays
            $expandingVars = $this->findFrontMatterVariables($value);
            if (!empty($expandingVars))
            {
                $value = $this->evaluateArrayType($key, $value, $expandingVars);
            }

            $wip[] = $value;
        }

        return $wip;
    }

    /**
     * Convert a string or an array into an array of ExpandedValue objects created through "value expansion".
     *
     * @param string   $frontMatterKey     The current hierarchy of the Front Matter keys being used
     * @param string   $expandableValue    The Front Matter value that will be expanded
     * @param string[] $arrayVariableNames The Front Matter variable names that reference arrays
     *
     * @throws YamlUnsupportedVariableException If a multidimensional array is given for value expansion
     *
     * @return array
     */
    private function evaluateArrayType($frontMatterKey, $expandableValue, $arrayVariableNames)
    {
        if (!is_array($expandableValue))
        {
            $expandableValue = [$expandableValue];
        }

        $this->expansionUsed = true;

        foreach ($arrayVariableNames as $variable)
        {
            $variableValue = $this->getVariableValue($frontMatterKey, $variable);

            if (ArrayUtilities::is_multidimensional($variableValue))
            {
                throw new YamlUnsupportedVariableException("Yaml array expansion is not supported with multidimensional arrays with `$variable` for key `$frontMatterKey`");
            }

            $wip = [];

            foreach ($expandableValue as &$statement)
            {
                foreach ($variableValue as $value)
                {
                    $evaluatedValue = ($statement instanceof ExpandedValue) ? clone $statement : new ExpandedValue($statement);

                    $varTemplate = $this->getVariableTemplate($variable);

                    $evaluatedValue->setEvaluated(str_replace($varTemplate, $value, $evaluatedValue->getEvaluated()));
                    $evaluatedValue->setIterator($variable, $value);

                    $wip[] = $evaluatedValue;
                }
            }

            $expandableValue = $wip;
        }

        return $expandableValue;
    }

    /**
     * Evaluate an string for FrontMatter variables and replace them with the corresponding values.
     *
     * @param string $key          The key of the Front Matter value
     * @param string $string       The string that will be evaluated
     * @param bool   $ignoreArrays When set to true, an exception won't be thrown when an array is found with the
     *                             interpolation
     *
     * @throws YamlUnsupportedVariableException A FrontMatter variable is not an int, float, or string
     *
     * @return string The final string with variables evaluated
     */
    private function evaluateBasicType($key, $string, $ignoreArrays = false)
    {
        $variables = $this->findFrontMatterVariables($string);

        foreach ($variables as $variable)
        {
            $value = $this->getVariableValue($key, $variable);

            if (is_array($value) || is_bool($value))
            {
                if ($ignoreArrays)
                {
                    continue;
                }

                throw new YamlUnsupportedVariableException("Yaml variable `$variable` for `$key` is not a supported data type.");
            }

            // The FM variable template that we need to replace with our evaluated value
            $varTemplate = $this->getVariableTemplate($variable);
            $string = str_replace($varTemplate, $value, $string);
        }

        return $string;
    }

    //
    // Variable management
    //

    /**
     * Get an array of FrontMatter variables in the specified string that need to be interpolated.
     *
     * @param string $string
     *
     * @return string[]
     */
    private function findFrontMatterVariables($string)
    {
        $primitiveVars = [];
        preg_match_all(self::VARIABLE_DEF, $string, $primitiveVars);

        $complexVars = [];
        preg_match_all(self::ARRAY_DEF, $string, $complexVars);

        // Default behavior causes $primitiveVars[0] is the entire string that was matched. $primitiveVars[1] will be each
        // matching result individually.
        return array_merge($primitiveVars[1], $complexVars[1]);
    }

    /**
     * Get the value of a FM variable.
     *
     * @param string $key     The FM key that is being currently evaluated (used solely for a helpful error message)
     * @param string $varName The variable name we're searching for without the `%`
     *
     * @throws YamlVariableUndefinedException when variable is not defined
     *
     * @return mixed
     */
    private function getVariableValue($key, $varName)
    {
        $isPrimitive = (strpos($varName, '.') === false);
        $variableVal = null;

        if ($isPrimitive)
        {
            $variableVal = ArrayUtilities::array_safe_get($this->frontMatter, $varName);
        }
        else
        {
            $variableVal = __::get($this->complexVariables, $varName);
        }

        if ($variableVal === null)
        {
            throw new YamlVariableUndefinedException("Yaml variable `$varName` is not defined for: $key");
        }

        return $variableVal;
    }

    /**
     * Get the variable template that needs to be replaced.
     *
     * The syntax for primitive variables differ from complex variables, so this method will return the appropriate
     * template that will be used to replace the value.
     *
     * @param string $variableName The variable name
     *
     * @return string
     */
    private function getVariableTemplate($variableName)
    {
        $isPrimitive = (strpos($variableName, '.') === false);

        return ($isPrimitive) ? sprintf('%%%s', $variableName) : sprintf('%%{%s}', $variableName);
    }

    //
    // Utility functions
    //

    /**
     * @param string $epochTime
     *
     * @return bool|\DateTime
     */
    private function castDateTimeTimezone($epochTime)
    {
        $timezone = new \DateTimeZone(date_default_timezone_get());
        $value = \DateTime::createFromFormat('U', $epochTime);
        $value->setTimezone($timezone);

        return $value;
    }

    /**
     * @param $guess
     *
     * @return bool|\DateTime
     */
    private function guessDateTime($guess)
    {
        if ($guess instanceof \DateTime)
        {
            return $guess;
        }
        elseif (is_numeric($guess))
        {
            return $this->castDateTimeTimezone($guess);
        }

        try
        {
            return new \DateTime($guess);
        }
        catch (\Exception $e)
        {
            return false;
        }
    }
}
