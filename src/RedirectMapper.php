<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

class RedirectMapper
{
    /** @var array<string, string> */
    private array $urlMap;

    public function __construct()
    {
        $this->urlMap = [];
    }

    public function registerRedirect($from, $to): void
    {
        $this->urlMap[$from] = $to;
    }

    public function getRedirects()
    {
        return $this->urlMap;
    }
}
