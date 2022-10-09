<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use __;
use ArrayAccess;
use Twig\Error\SyntaxError;
use Twig\TwigFilter;

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
     * @param array  $array      The elements to filter through
     * @param string $key        The key value in an associative array or FrontMatter
     * @param string $comparison The actual comparison symbols being used
     * @param mixed  $value      The value we're searching for
     *
     * @throws SyntaxError
     */
    public function __invoke(array $array, string $key, string $comparison, mixed $value): array
    {
        $results = [];

        foreach ($array as $item) {
            if ($this->compare($item, $key, $comparison, $value)) {
                $results[] = $item;
            }
        }

        return $results;
    }

    public static function get(): TwigFilter
    {
        return new TwigFilter('where', new self());
    }

    /**
     * The logic for determining if an element matches the filter.
     *
     * @param mixed  $item       The elements to filter through
     * @param string $key        The key value in an associative array or FrontMatter
     * @param string $comparison The actual comparison symbol being used
     * @param mixed  $value      The value we're searching for
     *
     * @throws SyntaxError
     */
    private function compare(mixed $item, string $key, string $comparison, mixed $value): bool
    {
        if ($this->compareNullValues($item, $key, $comparison, $value)) {
            return true;
        }

        $lhsValue = __::get($item, $key);

        if ($lhsValue === null) {
            return false;
        }

        return $this->comparisonSymbol($lhsValue, $comparison, $value);
    }

    /**
     * If the comparison is == or !=, then special behavior is defined for null values.
     *
     * @param ArrayAccess|mixed $item     The elements to filter through
     * @param string            $key      The key value in an associative array or FrontMatter
     * @param string            $operator The actual comparison symbol being used
     * @param mixed             $value    The value we're searching for
     */
    private function compareNullValues($item, $key, $operator, mixed $value): bool
    {
        if ($operator !== '==' && $operator !== '!=') {
            return false;
        }

        if (!__::has($item, $key)) {
            if ($operator === '==' && $value === null) {
                return true;
            }
            if ($operator === '!=' && $value !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws SyntaxError
     */
    private function comparisonSymbol(mixed $lhs, string $comparison, mixed $rhs): bool
    {
        return match ($comparison) {
            '==' => $lhs === $rhs,
            '!=' => $lhs !== $rhs,
            '>' => $lhs > $rhs,
            '>=' => $lhs >= $rhs,
            '<' => $lhs < $rhs,
            '<=' => $lhs <= $rhs,
            '~=' => $this->contains($lhs, $rhs),
            '_=' => $this->containsCaseInsensitive($lhs, $rhs),
            '/=' => $this->regexMatches($lhs, $rhs),
            default => throw new SyntaxError("Invalid where comparison ({$comparison})"),
        };
    }

    private function contains($haystack, $needle): bool
    {
        return
            (is_array($haystack) && in_array($needle, $haystack))
            || (is_string($haystack) && str_contains($haystack, (string)$needle));
    }

    private function containsCaseInsensitive($haystack, $needle): bool
    {
        if (is_array($haystack)) {
            $downCase = array_combine(array_map('strtolower', $haystack), $haystack);

            return isset($downCase[strtolower((string)$needle)]);
        }

        return is_string($haystack) && str_contains(strtolower($haystack), strtolower((string)$needle));
    }

    private function regexMatches($haystack, $regex): bool
    {
        return preg_match($regex, (string)$haystack) === 1;
    }
}
