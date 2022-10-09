<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

abstract class RuntimeStatus
{
    final public const BOOT_WITHOUT_CLEAN = 1;

    final public const COMPILER_PRESERVE_CASE = 2;

    final public const IN_SAFE_MODE = 4;

    final public const IN_PROFILE_MODE = 8;

    final public const IN_SERVE_MODE = 128;

    final public const USING_CACHE = 16;

    final public const USING_DRAFTS = 32;

    final public const USING_HIGHLIGHTER = 64;

    final public const USING_LINE_NUMBERS = 256;
}
