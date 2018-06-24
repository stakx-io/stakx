<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\EventSubscriber;

use allejo\stakx\Compiler;
use allejo\stakx\Logger;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use Kwf\FileWatcher\Event\Delete as DeleteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FileDeletionSubscriber implements EventSubscriberInterface
{
    private $compiler;
    private $logger;

    public function __construct(Compiler $compiler, Logger $logger)
    {
        $this->compiler = $compiler;
        $this->logger = $logger;
    }

    public function onFileDeletion(DeleteEvent $event)
    {
        $relFilePath = fs::getRelativePath($event->filename);
        $this->logger->writeln(sprintf('File deletion detected: %s', $relFilePath));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            DeleteEvent::NAME => 'onFileDeletion'
        ];
    }
}
