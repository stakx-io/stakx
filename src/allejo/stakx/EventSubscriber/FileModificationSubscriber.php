<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\EventSubscriber;

use allejo\stakx\Compiler;
use allejo\stakx\Core\StakxLogger;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use Kwf\FileWatcher\Event\Modify as ModifyEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FileModificationSubscriber implements EventSubscriberInterface
{
    private $compiler;
    private $logger;

    public function __construct(Compiler $compiler, StakxLogger $logger)
    {
        $this->compiler = $compiler;
        $this->logger = $logger;
    }

    public function onFileModification(ModifyEvent $event)
    {
        $relFilePath = fs::getRelativePath($event->filename);
        $this->logger->writeln(sprintf('File change detected: %s', $relFilePath));
    }

    public static function getSubscribedEvents()
    {
        return [
            ModifyEvent::NAME => 'onFileModification'
        ];
    }
}
