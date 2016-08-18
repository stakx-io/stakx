<?php

namespace allejo\stakx\Manager;

use allejo\stakx\Core\ConsoleInterface;
use allejo\stakx\System\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ItemManager
{
    /**
     * @var ConsoleInterface
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
     * @param ConsoleInterface|OutputInterface|null $output
     */
    public function setConsoleOutput ($output)
    {
        $this->output = new ConsoleInterface($output);
    }
}