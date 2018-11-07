<?php

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
     * @param array $customVariables
     *
     * @since 0.2.0
     */
    public function appendCustomVariables(array $customVariables)
    {
        $this->customVariables = array_merge($this->customVariables, $customVariables);
    }
}
