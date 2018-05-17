<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\EventSubscriber;

use allejo\stakx\Compiler;
use allejo\stakx\Core\StakxLogger;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use Kwf\FileWatcher\Event\AbstractEvent;
use Kwf\FileWatcher\Event\Create;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FileCreationSubscriber implements EventSubscriberInterface
{
    private $compiler;
    private $logger;

    public function __construct(Compiler $compiler, StakxLogger $logger)
    {
        $this->compiler = $compiler;
        $this->logger = $logger;
    }

    public function onFileCreation(AbstractEvent $event)
    {
        $relFilePath = fs::getRelativePath($event->filename);
        $this->logger->writeln(sprintf('File creation detected: %s', $relFilePath));

//        try
//        {
//
//        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Create::NAME => 'onFileCreation',
        ];
    }
}
