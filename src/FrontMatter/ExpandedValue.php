<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\FrontMatter;

use Stringable;

/**
 * Class ExpandedValue.
 *
 * An instance of this object is created to store the values and information related to the "value expansion" feature of
 * the FrontMatter parser
 */
class ExpandedValue implements Stringable
{
    /**
     * The definition of variable replacements that occurred for this specific string.
     *
     * The $key for the array would be the variable name without the % and the $value would be literal value that
     * replaced the variable in the string
     *
     * @var string[]
     */
    private array $iterators;

    /**
     * ExpandedValue constructor.
     *
     * @param string $evaluated
     */
    public function __construct(
        /**
         * Store the original value with variables and this value will be updated in-place as it's evaluated.
         */
        private $evaluated
    ) {
    }

    public function __toString(): string
    {
        return $this->getEvaluated();
    }

    /**
     * Get the current evaluated string.
     *
     * If the evaluation in FrontMatterParser hasn't been completed, this will return the partially evaluated string
     * variables still in place.
     */
    public function getEvaluated(): string
    {
        return $this->evaluated;
    }

    /**
     * Update the currently evaluated string.
     *
     * As the string is being evaluated in the FrontMatterParser, this value will be updated as variables are iterated
     * through.
     *
     * @param string $string
     */
    public function setEvaluated($string): void
    {
        $this->evaluated = $string;
    }

    /**
     * Get all of the variable replacements that were used in this evaluated string.
     *
     * @return string[]
     */
    public function getIterators(): array
    {
        return $this->iterators;
    }

    /**
     * Record the value of a variable replacement.
     *
     * The variable name should NOT contain the % and the value should be as-is
     *
     * @param string $variableName
     */
    public function setIterator($variableName, int|string $variableValue): void
    {
        $this->iterators[$variableName] = $variableValue;
    }
}
