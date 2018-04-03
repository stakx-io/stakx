<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use Twig_Error_Syntax;

/**
 * Where Twig Filter.
 *
 * This Twig filter introduces the `where` filter
 *
 * Usage:
 *   <array> | where(<key>, <comparison>, <value>)
 *
 * Available comparisons:
 *   - ==  Ensure the values are equal and are the same data type
 *   - !=  Ensure the values are not equal; returns false if the values are the same but different data types
 *   - >   Greater than
 *   - >=  Greater than or equal to
 *   - <   Less than
 *   - <=  Less than or equal to
 *   - ~=  Check if a string or array contains the <value>; case-sensitive
 *   - _=  Check if a string or array contains the <value>; case-insensitive
 *   - /=  Compare the <value> with a regular expression
 */
class WhereFilter extends AbstractTwigExtension implements TwigFilterInterface
{
    /**
     * @param array|\ArrayAccess[] $array      The elements to filter through
     * @param string               $key        The key value in an associative array or FrontMatter
     * @param string               $comparison The actual comparison symbols being used
     * @param mixed                $value      The value we're searching for
     *
     * @return array
     */
    public function __invoke($array, $key, $comparison, $value)
    {
        $results = array();
        $this->search_r($array, $key, $comparison, $value, $results);

        return $results;
    }

    /**
     * @return \Twig_SimpleFilter
     */
    public static function get()
    {
        return new \Twig_SimpleFilter('where', new self());
    }

    /**
     * Recursive searching calling our comparison.
     *
     * @param array|\ArrayAccess[] $array      The elements to filter through
     * @param string               $key        The key value in an associative array or FrontMatter
     * @param string               $comparison The actual comparison symbols being used
     * @param string               $value      The value we're searching for
     * @param array                $results    The reference to where to keep the filtered elements
     */
    private function search_r($array, $key, $comparison, $value, &$results)
    {
        if (!is_array($array) && !($array instanceof \ArrayAccess))
        {
            return;
        }

        if ($this->compare($array, $key, $comparison, $value))
        {
            $results[] = $array;
        }

        foreach ($array as $subarray)
        {
            $this->search_r($subarray, $key, $comparison, $value, $results);
        }
    }

    /**
     * The logic for determining if an element matches the filter.
     *
     * @param array|\ArrayAccess[] $array      The elements to filter through
     * @param string               $key        The key value in an associative array or FrontMatter
     * @param string               $comparison The actual comparison symbols being used
     * @param string               $value      The value we're searching for
     *
     * @throws Twig_Error_Syntax
     *
     * @return bool
     */
    private function compare($array, $key, $comparison, $value)
    {
        if ($this->compareNullValues($array, $key, $comparison, $value))
        {
            return true;
        }

        if (!isset($array[$key]))
        {
            return false;
        }

        return $this->comparisonSymbol($array[$key], $comparison, $value);
    }

    /**
     * If the comparison is == or !=, then special behavior is defined for null values.
     *
     * @param array|\ArrayAccess[] $array      The elements to filter through
     * @param string               $key        The key value in an associative array or FrontMatter
     * @param string               $comparison The actual comparison symbols being used
     * @param mixed                $value      The value we're searching for
     *
     * @return bool
     */
    private function compareNullValues($array, $key, $comparison, $value)
    {
        if ($comparison != '==' && $comparison != '!=')
        {
            return false;
        }

        if (!($array instanceof \ArrayAccess))
        {
            return false;
        }

        if (!isset($array[$key]))
        {
            if ($comparison == '==' && is_null($value))
            {
                return true;
            }
            if ($comparison == '!=' && !is_null($value))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed  $lhs
     * @param string $comparison
     * @param mixed  $rhs
     *
     * @throws Twig_Error_Syntax
     *
     * @return bool
     */
    private function comparisonSymbol($lhs, $comparison, $rhs)
    {
        switch ($comparison)
        {
            case '==':
                return ($lhs === $rhs);

            case '!=':
                return ($lhs !== $rhs);

            case '>':
                return ($lhs > $rhs);

            case '>=':
                return ($lhs >= $rhs);

            case '<':
                return ($lhs < $rhs);

            case '<=':
                return ($lhs <= $rhs);

            case '~=':
                return $this->contains($lhs, $rhs);

            case '_=':
                return $this->containsCaseInsensitive($lhs, $rhs);

            case '/=':
                return $this->regexMatches($lhs, $rhs);

            default:
                throw new Twig_Error_Syntax("Invalid where comparison ({$comparison})");
        }
    }

    private function contains($haystack, $needle)
    {
        return
            (is_array($haystack) && in_array($needle, $haystack)) ||
            (is_string($haystack) && strpos($haystack, $needle) !== false);
    }

    private function containsCaseInsensitive($haystack, $needle)
    {
        if (is_array($haystack))
        {
            $downCase = array_combine(array_map('strtolower', $haystack), $haystack);

            return isset($downCase[strtolower($needle)]);
        }

        return is_string($haystack) && strpos(strtolower($haystack), strtolower($needle)) !== false;
    }

    private function regexMatches($haystack, $regex)
    {
        return preg_match($regex, $haystack) === 1;
    }
}
