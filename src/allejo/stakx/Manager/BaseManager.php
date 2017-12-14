<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\System\Filesystem;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseManager implements LoggerAwareInterface, ContainerAwareInterface
{
    protected static $documentIgnoreList = array('/\.example$/');

    /** @var Container */
    protected $container;
    /** @var LoggerInterface */
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

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Build the manager's internals after it's been configured.
     */
    public function compileManager()
    {

    }
}
