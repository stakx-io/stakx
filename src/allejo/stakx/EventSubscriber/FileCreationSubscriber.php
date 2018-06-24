<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\EventSubscriber;

use allejo\stakx\Compiler;
use allejo\stakx\Logger;
use allejo\stakx\FileMapper;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use Kwf\FileWatcher\Event\Create as CreateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FileCreationSubscriber implements EventSubscriberInterface
{
    private $fileMapper;
    private $compiler;
    private $logger;

    public function __construct(Compiler $compiler, FileMapper $fileMapper, Logger $logger)
    {
        $this->fileMapper = $fileMapper;
        $this->compiler = $compiler;
        $this->logger = $logger;
    }

    public function onFileCreation(CreateEvent $event)
    {
        $relFilePath = fs::getRelativePath($event->filename);
        $this->logger->writeln(sprintf('File creation detected: %s', $relFilePath));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CreateEvent::NAME => 'onFileCreation',
        ];
    }
}
