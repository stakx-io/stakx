<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

abstract class BaseManager
{
    protected static $documentIgnoreList = ['/\.example$/'];

    /**
     * Build the manager's internals after it's been configured.
     */
    public function compileManager()
    {
    }
}
