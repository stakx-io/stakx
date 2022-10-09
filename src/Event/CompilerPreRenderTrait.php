<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

trait CompilerPreRenderTrait
{
    protected $customVariables = [];

    public function getCustomVariables()
    {
        return $this->customVariables;
    }

    /**
     * Append custom variables that'll be made available as template variables in PageViews.
     *
     * @since 0.2.0
     */
    public function appendCustomVariables(array $customVariables): void
    {
        $this->customVariables = array_merge($this->customVariables, $customVariables);
    }
}
