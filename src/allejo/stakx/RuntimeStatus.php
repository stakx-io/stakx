<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

abstract class RuntimeStatus
{
    const BOOT_WITHOUT_CLEAN = 1;

    const COMPILER_PRESERVE_CASE = 2;

    const IN_SAFE_MODE = 4;
    const IN_PROFILE_MODE = 8;

    const USING_CACHE = 16;
    const USING_DRAFTS = 32;
    const USING_HIGHLIGHTER = 64;
}
