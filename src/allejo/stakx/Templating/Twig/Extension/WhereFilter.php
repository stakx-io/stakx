<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use __;
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
     * @throws Twig_Error_Syntax
     *
     * @return array
     */
    public function __invoke($array, $key, $comparison, $value)
    {
        $results = [];

        foreach ($array as $item)
        {
            if ($this->compare($item, $key, $comparison, $value))
            {
                $results[] = $item;
            }
        }

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
     * The logic for determining if an element matches the filter.
     *
     * @param mixed|\ArrayAccess $item       The elements to filter through
     * @param string             $key        The key value in an associative array or FrontMatter
     * @param string             $comparison The actual comparison symbol being used
     * @param string             $value      The value we're searching for
     *
     * @throws Twig_Error_Syntax
     *
     * @return bool
     */
    private function compare($item, $key, $comparison, $value)
    {
        if ($this->compareNullValues($item, $key, $comparison, $value))
        {
            return true;
        }

        $lhsValue = __::get($item, $key);

        if ($lhsValue === null)
        {
            return false;
        }

        return $this->comparisonSymbol($lhsValue, $comparison, $value);
    }

    /**
     * If the comparison is == or !=, then special behavior is defined for null values.
     *
     * @param mixed|\ArrayAccess $item     The elements to filter through
     * @param string             $key      The key value in an associative array or FrontMatter
     * @param string             $operator The actual comparison symbol being used
     * @param mixed              $value    The value we're searching for
     *
     * @return bool
     */
    private function compareNullValues($item, $key, $operator, $value)
    {
        if ($operator != '==' && $operator != '!=')
        {
            return false;
        }

        if (!__::has($item, $key))
        {
            if ($operator == '==' && $value === null)
            {
                return true;
            }
            if ($operator == '!=' && $value !== null)
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
                return $lhs === $rhs;

            case '!=':
                return $lhs !== $rhs;

            case '>':
                return $lhs > $rhs;

            case '>=':
                return $lhs >= $rhs;

            case '<':
                return $lhs < $rhs;

            case '<=':
                return $lhs <= $rhs;

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
