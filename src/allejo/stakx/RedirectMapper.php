<?php

namespace allejo\stakx;

class RedirectMapper
{
    /** @var array<string, string> */
    private $urlMap;

    public function __construct()
    {
        $this->urlMap = [];
    }

    public function registerRedirect($from, $to)
    {
        $this->urlMap[$from] = $to;
    }

    public function getRedirects()
    {
        return $this->urlMap;
    }
}
