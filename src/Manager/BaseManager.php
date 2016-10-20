<?php

namespace allejo\stakx\Manager;

use allejo\stakx\Core\StakxLogger;
use allejo\stakx\System\Filesystem;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseManager implements LoggerAwareInterface
{
    /**
     * @var StakxLogger
     */
    protected $output;

    /**
     * @var Filesystem
     */
    protected $fs;

    public function __construct ()
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