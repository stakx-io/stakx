<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\System\Filesystem;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

abstract class BaseManager implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $output;

    /**
     * @var Filesystem
     */
    protected $fs;

    public function __construct()
    {
        $this->fs = new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->output = $logger;
    }
}
