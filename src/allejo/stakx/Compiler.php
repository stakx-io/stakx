<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\CollectableItem;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\PermalinkDocument;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Document\TemplateReadyDocument;
use allejo\stakx\Event\CompilerPostRenderDynamicPageView;
use allejo\stakx\Event\CompilerPostRenderRepeaterPageView;
use allejo\stakx\Event\CompilerPostRenderStaticPageView;
use allejo\stakx\Event\CompilerPreRenderDynamicPageView;
use allejo\stakx\Event\CompilerPreRenderRepeaterPageView;
use allejo\stakx\Event\CompilerPreRenderStaticPageView;
use allejo\stakx\Event\CompilerTemplateCreation;
use allejo\stakx\Event\RedirectPreOutput;
use allejo\stakx\Exception\FileAwareException;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FileExplorerDefinition;
use allejo\stakx\Filesystem\WritableFolder;
use allejo\stakx\FrontMatter\ExpandedValue;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\Manager\MenuManager;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Manager\TrackingManager;
use allejo\stakx\Templating\TemplateBridgeInterface;
use allejo\stakx\Templating\TemplateErrorInterface;
use allejo\stakx\Templating\TemplateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This class takes care of rendering the Twig body of PageViews with the respective information and it also takes care
 * of writing the rendered Twig to the filesystem.
 *
 * @since 0.1.1
 */
class Compiler
{
    /** @var string|false */
    private $redirectTemplate;

    /**
     * All of the PageViews handled by this Compiler instance indexed by their file paths relative to the site root.
     *
     * ```
     * array['_pages/index.html.twig'] = &PageView;
     * ```
     *
     * @var BasePageView[]
     */
    private $pageViewsFlattened;

    /** @var string[] */
    private $templateMapping;

    /** @var WritableFolder */
    private $folder;

    /** @var string */
    private $theme;

    /** @var TrackingManager[] */
    private $managers;

    private $templateBridge;
    private $pageManager;
    private $eventDispatcher;
    private $configuration;
    private $logger;

    public function __construct(
        TemplateBridgeInterface $templateBridge,
        Configuration $configuration,
        CollectionManager $collectionManager,
        DataManager $dataManager,
        MenuManager $menuManager,
        PageManager $pageManager,
        RedirectMapper $redirectMapper,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->templateBridge = $templateBridge;
        $this->theme = '';
        $this->pageManager = $pageManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->configuration = $configuration;

        $this->pageViewsFlattened = &$pageManager->getPageViewsFlattened();
        $this->redirectTemplate = $this->configuration->getRedirectTemplate();

        $this->managers['collection'] = $collectionManager;
        $this->managers['data'] = $dataManager;
        $this->managers['page'] = $pageManager;

        // Global variables maintained by stakx
        $this->templateBridge->setGlobalVariable('site', $configuration->getConfiguration());
        $this->templateBridge->setGlobalVariable('data', $dataManager->getJailedDataItems());
        $this->templateBridge->setGlobalVariable('collections', $collectionManager->getJailedCollections());
        $this->templateBridge->setGlobalVariable('menu', $menuManager->getSiteMenu());
        $this->templateBridge->setGlobalVariable('pages', $pageManager->getJailedStaticPageViews());
        $this->templateBridge->setGlobalVariable('repeaters', $pageManager->getJailedRepeaterPageViews());
        $this->templateBridge->setGlobalVariable('redirects', $redirectMapper->getRedirects());
    }

    /**
     * @param WritableFolder $folder
     */
    public function setTargetFolder(WritableFolder $folder)
    {
        $this->folder = $folder;
    }

    /**
     * @param string $themeName
     */
    public function setThemeName($themeName)
    {
        $this->theme = $themeName;
    }

    ///
    // Files and folders we listen to
    ///

    /**
     * @return FileExplorerDefinition[]
     */
    public function getFolderDefinitions()
    {
        $results = [];

        foreach ($this->managers as $manager)
        {
            $results += $manager->getFolderDefinitions();
        }

        return $results;
    }

    /**
     * Trigger a refresh of a file
     *
     * @param File $filePath
     *
     * @return bool
     */
    public function refreshFile(File $filePath)
    {
        foreach ($this->managers as $name => $manager)
        {
            if ($manager->isTracked($filePath))
            {
                // @TODO Start implementing this refresh logic for managers
                $manager->refreshItem($filePath);

                return true;
            }
        }

        return false;
    }

    ///
    // Twig parent templates
    ///

    public function getTemplateBridge()
    {
        return $this->templateBridge;
    }

    public function getTemplateMappings()
    {
        return $this->templateMapping;
    }

    ///
    // Rendering HTML Functionality
    ///

    /**
     * Get the HTML for a Static PageView.
     *
     * This function just **renders** the HTML but does not write it to the filesystem. Use `compilePageView()` for that
     * instead.
     *
     * @param StaticPageView $pageView
     *
     * @throws TemplateErrorInterface
     *
     * @return string the HTML for a Static PageView
     */
    public function renderStaticPageView(StaticPageView $pageView)
    {
        $pageView->compile();

        return $this->buildStaticPageViewHTML($pageView);
    }

    /**
     * Get the HTML for a Dynamic PageView and ContentItem.
     *
     * This function just **renders** the HTML but does not write it to the filesystem. Use `compileDynamicPageView()`
     * for that instead.
     *
     * @param DynamicPageView       $pageView
     * @param TemplateReadyDocument $contentItem
     *
     * @throws TemplateErrorInterface
     *
     * @return string
     */
    public function renderDynamicPageView(DynamicPageView $pageView, TemplateReadyDocument $contentItem)
    {
        $template = $this->createTwigTemplate($pageView);

        return $this->buildDynamicPageViewHTML($template, $contentItem);
    }

    /**
     * Get the HTML for a Repeater PageView.
     *
     * @param RepeaterPageView $pageView
     * @param ExpandedValue    $expandedValue
     *
     * @throws TemplateErrorInterface
     *
     * @return string
     */
    public function renderRepeaterPageView(RepeaterPageView $pageView, ExpandedValue $expandedValue)
    {
        $template = $this->createTwigTemplate($pageView);

        return $this->buildRepeaterPageViewHTML($template, $pageView, $expandedValue);
    }

    ///
    // IO Functionality
    ///

    /**
     * Compile all of the PageViews registered with the compiler.
     *
     * @since 0.1.0
     */
    public function compileAll()
    {
        foreach ($this->pageViewsFlattened as &$pageView)
        {
            $this->compilePageView($pageView);
        }
    }

    /**
     * Compile an individual PageView item.
     *
     * This function will take care of determining *how* to treat the PageView and write the compiled output to a the
     * respective target file.
     *
     * @param DynamicPageView|RepeaterPageView|StaticPageView $pageView The PageView that needs to be compiled
     *
     * @since 0.1.1
     */
    public function compilePageView(BasePageView &$pageView)
    {
        Service::setOption('currentTemplate', $pageView->getAbsoluteFilePath());
        $this->logger->debug('Compiling {type} PageView: {pageview}', [
            'pageview' => $pageView->getRelativeFilePath(),
            'type' => $pageView->getType(),
        ]);

        try
        {
            switch ($pageView->getType())
            {
                case BasePageView::STATIC_TYPE:
                    $this->compileStaticPageView($pageView);
                    $this->compileStandardRedirects($pageView);
                    break;

                case BasePageView::DYNAMIC_TYPE:
                    $this->compileDynamicPageView($pageView);
                    $this->compileStandardRedirects($pageView);
                    break;

                case BasePageView::REPEATER_TYPE:
                    $this->compileRepeaterPageView($pageView);
                    $this->compileExpandedRedirects($pageView);
                    break;
            }
        }
        catch (TemplateErrorInterface $e)
        {
            throw new FileAwareException(
                $e->getMessage(),
                $e->getCode(),
                $e,
                $pageView->getRelativeFilePath(),
                $e->getTemplateLine() + $pageView->getLineOffset()
            );
        }
    }

    /**
     * Write the compiled output for a static PageView.
     *
     * @since 0.1.1
     *
     * @throws TemplateErrorInterface
     */
    private function compileStaticPageView(StaticPageView &$pageView)
    {
        $this->writeToFilesystem(
            $pageView->getTargetFile(),
            $this->renderStaticPageView($pageView),
            BasePageView::STATIC_TYPE
        );
    }

    /**
     * Write the compiled output for a dynamic PageView.
     *
     * @param DynamicPageView $pageView
     *
     * @since 0.1.1
     *
     * @throws TemplateErrorInterface
     */
    private function compileDynamicPageView(DynamicPageView &$pageView)
    {
        $contentItems = $pageView->getCollectableItems();
        $template = $this->createTwigTemplate($pageView);

        foreach ($contentItems as &$contentItem)
        {
            if ($contentItem->isDraft() && !Service::hasRunTimeFlag(RuntimeStatus::USING_DRAFTS))
            {
                $this->logger->debug('{file}: marked as a draft', [
                    'file' => $contentItem->getRelativeFilePath(),
                ]);

                continue;
            }

            $this->writeToFilesystem(
                $contentItem->getTargetFile(),
                $this->buildDynamicPageViewHTML($template, $contentItem),
                BasePageView::DYNAMIC_TYPE
            );

            $this->compileStandardRedirects($contentItem);
        }
    }

    /**
     * Write the compiled output for a repeater PageView.
     *
     * @param RepeaterPageView $pageView
     *
     * @since 0.1.1
     *
     * @throws TemplateErrorInterface
     */
    private function compileRepeaterPageView(RepeaterPageView &$pageView)
    {
        $template = $this->createTwigTemplate($pageView);
        $permalinks = $pageView->getRepeaterPermalinks();

        foreach ($permalinks as $permalink)
        {
            $this->writeToFilesystem(
                $pageView->getTargetFile($permalink->getEvaluated()),
                $this->buildRepeaterPageViewHTML($template, $pageView, $permalink),
                BasePageView::REPEATER_TYPE
            );
        }
    }

    /**
     * Write the given $output to the $targetFile as a $fileType PageView.
     *
     * @param string $targetFile
     * @param string $output
     * @param string $fileType
     */
    private function writeToFilesystem($targetFile, $output, $fileType)
    {
        $this->logger->notice('Writing {type} PageView file: {file}', [
            'type' => $fileType,
            'file' => $targetFile,
        ]);
        $this->folder->writeFile($targetFile, $output);
    }

    ///
    // Redirect handling
    ///

    /**
     * Write redirects for standard redirects.
     *
     * @throws TemplateErrorInterface
     *
     * @since 0.1.1
     */
    private function compileStandardRedirects(PermalinkDocument &$pageView)
    {
        $redirects = $pageView->getRedirects();

        foreach ($redirects as $redirect)
        {
            $redirectPageView = BasePageView::createRedirect(
                $redirect,
                $pageView->getPermalink(),
                $this->redirectTemplate
            );
            $redirectPageView->evaluateFrontMatter([], [
                'site' => $this->configuration->getConfiguration(),
            ]);

            $redirectEvent = new RedirectPreOutput(
                $redirect,
                $pageView->getPermalink(),
                $pageView,
                $redirectPageView
            );
            $this->eventDispatcher->dispatch(RedirectPreOutput::NAME, $redirectEvent);

            $this->compileStaticPageView($redirectPageView);
        }
    }

    /**
     * Write redirects for expanded redirects.
     *
     * @param RepeaterPageView $pageView
     *
     * @since 0.1.1
     */
    private function compileExpandedRedirects(RepeaterPageView &$pageView)
    {
        $permalinks = $pageView->getRepeaterPermalinks();

        /** @var ExpandedValue[] $repeaterRedirect */
        foreach ($pageView->getRepeaterRedirects() as $repeaterRedirect)
        {
            /**
             * @var int
             * @var ExpandedValue $redirect
             */
            foreach ($repeaterRedirect as $index => $redirect)
            {
                $redirectPageView = BasePageView::createRedirect(
                    $redirect->getEvaluated(),
                    $permalinks[$index]->getEvaluated(),
                    $this->redirectTemplate
                );
                $redirectPageView->evaluateFrontMatter([], [
                    'site' => $this->configuration->getConfiguration(),
                ]);

                $redirectEvent = new RedirectPreOutput(
                    $redirect->getEvaluated(),
                    $permalinks[$index]->getEvaluated(),
                    $pageView,
                    $redirectPageView
                );
                $this->eventDispatcher->dispatch(RedirectPreOutput::NAME, $redirectEvent);

                $this->compilePageView($redirectPageView);
            }
        }
    }

    ///
    // Twig Functionality
    ///

    /**
     * Get the compiled HTML for a specific iteration of a repeater PageView.
     *
     * @param TemplateInterface $template
     * @param RepeaterPageView  $pageView
     * @param ExpandedValue     $expandedValue
     *
     * @since  0.1.1
     *
     * @return string
     */
    private function buildRepeaterPageViewHTML(TemplateInterface &$template, RepeaterPageView &$pageView, ExpandedValue &$expandedValue)
    {
        $defaultContext = [
            'this' => $pageView->createJail(),
        ];

        $pageView->evaluateFrontMatter([
            'permalink' => $expandedValue->getEvaluated(),
            'iterators' => $expandedValue->getIterators(),
        ]);

        $preEvent = new CompilerPreRenderRepeaterPageView($pageView, $expandedValue);
        $this->eventDispatcher->dispatch(CompilerPreRenderRepeaterPageView::NAME, $preEvent);

        $context = array_merge($preEvent->getCustomVariables(), $defaultContext);
        $output = $template
            ->render($context)
        ;

        $postEvent = new CompilerPostRenderRepeaterPageView($pageView, $expandedValue, $output);
        $this->eventDispatcher->dispatch(CompilerPostRenderRepeaterPageView::NAME, $postEvent);

        return $postEvent->getCompiledOutput();
    }

    /**
     * Get the compiled HTML for a specific ContentItem.
     *
     * @param CollectableItem|TemplateReadyDocument $twigItem
     *
     * @since  0.1.1
     *
     * @return string
     */
    private function buildDynamicPageViewHTML(TemplateInterface &$template, &$twigItem)
    {
        $defaultContext = [
            'this' => $twigItem->createJail(),
        ];

        $preEvent = new CompilerPreRenderDynamicPageView($twigItem);
        $this->eventDispatcher->dispatch(CompilerPreRenderDynamicPageView::NAME, $preEvent);

        $context = array_merge($preEvent->getCustomVariables(), $defaultContext);
        $output = $template
            ->render($context)
        ;

        $postEvent = new CompilerPostRenderDynamicPageView($twigItem, $output);
        $this->eventDispatcher->dispatch(CompilerPostRenderDynamicPageView::NAME, $postEvent);

        return $postEvent->getCompiledOutput();
    }

    /**
     * Get the compiled HTML for a static PageView.
     *
     * @since  0.1.1
     *
     * @throws TemplateErrorInterface
     *
     * @return string
     */
    private function buildStaticPageViewHTML(StaticPageView &$pageView)
    {
        $defaultContext = [
            'this' => $pageView->createJail(),
        ];

        $preEvent = new CompilerPreRenderStaticPageView($pageView);
        $this->eventDispatcher->dispatch(CompilerPreRenderStaticPageView::NAME, $preEvent);

        $context = array_merge($preEvent->getCustomVariables(), $defaultContext);
        $output = $this
            ->createTwigTemplate($pageView)
            ->render($context)
        ;

        $postEvent = new CompilerPostRenderStaticPageView($pageView, $output);
        $this->eventDispatcher->dispatch(CompilerPostRenderStaticPageView::NAME, $postEvent);

        return $postEvent->getCompiledOutput();
    }

    /**
     * Create a Twig template that just needs an array to render.
     *
     * @since  0.1.1
     *
     * @throws TemplateErrorInterface
     *
     * @return TemplateInterface
     */
    private function createTwigTemplate(BasePageView &$pageView)
    {
        try
        {
            $template = $this->templateBridge->createTemplate($pageView->getContent());

            $this->templateMapping[$template->getTemplateName()] = $pageView->getRelativeFilePath();

            $event = new CompilerTemplateCreation($pageView, $template, $this->theme);
            $this->eventDispatcher->dispatch(CompilerTemplateCreation::NAME, $event);

            return $template;
        }
        catch (TemplateErrorInterface $e)
        {
            $e
                ->setTemplateLine($e->getTemplateLine() + $pageView->getLineOffset())
                ->setContent($pageView->getContent())
                ->setName($pageView->getRelativeFilePath())
                ->setRelativeFilePath($pageView->getRelativeFilePath())
                ->buildException()
            ;

            throw $e;
        }
    }
}
