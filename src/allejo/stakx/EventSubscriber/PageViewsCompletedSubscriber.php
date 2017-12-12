<?php

namespace allejo\stakx\EventSubscriber;

use allejo\stakx\Configuration;
use allejo\stakx\Event\PageViewsCompleted;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\Manager\MenuManager;
use allejo\stakx\Manager\PageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PageViewsCompletedSubscriber implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageViewsCompleted::NAME => 'onPageViewsCompleted'
        ];
    }

    public function onPageViewsCompleted()
    {
        /** @var \Twig_Environment $twig */
        $twig = $this->container->get('templating');
        $twig->addGlobal('site', $this->container->get(Configuration::class)->getconfiguration());

        $dataItems = [];
        if ($this->container->has(DataManager::class)) {
            $dataItems = $this->container->get(DataManager::class)->getJailedDataItems();
        }
        $twig->addGlobal('data', $dataItems);

        $collectionItems = [];
        if ($this->container->has(CollectionManager::class)) {
            $collectionItems = $this->container->get(CollectionManager::class)->getJailedCollections();
        }
        $twig->addGlobal('collections', $collectionItems);

        $twig->addGlobal('menu', $this->container->get(MenuManager::class)->getSiteMenu());
        $twig->addGlobal('pages', $this->container->get(PageManager::class)->getJailedStaticPageViews());
    }
}