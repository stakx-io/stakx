<?php declare(strict_types=1);

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
use allejo\stakx\Filesystem\WritableFolder;
use allejo\stakx\FrontMatter\ExpandedValue;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\Manager\MenuManager;
use allejo\stakx\Manager\PageManager;
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
    private string|false $redirectTemplate;

    /**
     * All the PageViews handled by this Compiler instance indexed by their file paths relative to the site root.
     *
     * ```
     * array['_pages/index.html.twig'] = &PageView;
     * ```
     *
     * @var BasePageView[]
     */
    private array $pageViewsFlattened;

    /** @var string[] */
    private array $templateMapping;

    private WritableFolder $folder;

    private string $theme;

    public function __construct(
        private readonly TemplateBridgeInterface $templateBridge,
        private readonly Configuration $configuration,
        CollectionManager $collectionManager,
        DataManager $dataManager,
        MenuManager $menuManager,
        private readonly PageManager $pageManager,
        RedirectMapper $redirectMapper,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger
    ) {
        $this->theme = '';

        $this->pageViewsFlattened = &$pageManager->getPageViewsFlattened();
        $this->redirectTemplate = $this->configuration->getRedirectTemplate();

        // Global variables maintained by stakx
        $this->templateBridge->setGlobalVariable('site', $configuration->getConfiguration());
        $this->templateBridge->setGlobalVariable('data', $dataManager->getJailedDataItems());
        $this->templateBridge->setGlobalVariable('collections', $collectionManager->getJailedCollections());
        $this->templateBridge->setGlobalVariable('menu', $menuManager->getSiteMenu());
        $this->templateBridge->setGlobalVariable('pages', $pageManager->getJailedStaticPageViews());
        $this->templateBridge->setGlobalVariable('repeaters', $pageManager->getJailedRepeaterPageViews());
        $this->templateBridge->setGlobalVariable('redirects', $redirectMapper->getRedirects());
    }

    public function setTargetFolder(WritableFolder $folder): void
    {
        $this->folder = $folder;
    }

    public function setThemeName(string $themeName): void
    {
        $this->theme = $themeName;
    }

    //
    // Twig parent templates
    //

    public function getTemplateBridge(): TemplateBridgeInterface
    {
        return $this->templateBridge;
    }

    /**
     * @return string[]
     */
    public function getTemplateMappings(): array
    {
        return $this->templateMapping;
    }

    //
    // Rendering HTML Functionality
    //

    /**
     * Get the HTML for a Static PageView.
     *
     * This function just **renders** the HTML but does not write it to the filesystem. Use `compilePageView()` for that
     * instead.
     *
     * @throws TemplateErrorInterface
     *
     * @return string the HTML for a Static PageView
     */
    public function renderStaticPageView(StaticPageView $pageView): string
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
     * @throws TemplateErrorInterface
     */
    public function renderDynamicPageView(DynamicPageView $pageView, TemplateReadyDocument $contentItem): string
    {
        $template = $this->createTwigTemplate($pageView);

        return $this->buildDynamicPageViewHTML($template, $contentItem);
    }

    /**
     * Get the HTML for a Repeater PageView.
     *
     * @throws TemplateErrorInterface
     */
    public function renderRepeaterPageView(RepeaterPageView $pageView, ExpandedValue $expandedValue): string
    {
        $template = $this->createTwigTemplate($pageView);

        return $this->buildRepeaterPageViewHTML($template, $pageView, $expandedValue);
    }

    //
    // IO Functionality
    //

    /**
     * Compile all PageViews registered with the compiler.
     *
     * @throws FileAwareException
     *
     * @since 0.1.0
     */
    public function compileAll(): void
    {
        foreach ($this->pageViewsFlattened as $pageView) {
            $this->compilePageView($pageView);
        }
    }

    /**
     * Compile an individual PageView item.
     *
     * This function will take care of determining *how* to treat the PageView and write the compiled output to a the
     * respective target file.
     *
     * @param BasePageView $pageView The PageView that needs to be compiled
     *
     * @throws FileAwareException
     *
     * @since 0.1.1
     */
    public function compilePageView(BasePageView $pageView): void
    {
        Service::setOption('currentTemplate', $pageView->getAbsoluteFilePath());
        $this->logger->debug('Compiling {type} PageView: {pageview}', [
            'pageview' => $pageView->getRelativeFilePath(),
            'type' => $pageView->getType(),
        ]);

        try {
            switch ($pageView->getType()) {
                case BasePageView::STATIC_TYPE:
                    /** @var StaticPageView $pageView */
                    $this->compileStaticPageView($pageView);
                    $this->compileStandardRedirects($pageView);

                    break;

                case BasePageView::DYNAMIC_TYPE:
                    /** @var DynamicPageView $pageView */
                    $this->compileDynamicPageView($pageView);
                    $this->compileStandardRedirects($pageView);

                    break;

                case BasePageView::REPEATER_TYPE:
                    /** @var RepeaterPageView $pageView */
                    $this->compileRepeaterPageView($pageView);
                    $this->compileExpandedRedirects($pageView);

                    break;
            }
        } catch (TemplateErrorInterface $e) {
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
    private function compileStaticPageView(StaticPageView $pageView): void
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
     * @since 0.1.1
     *
     * @throws TemplateErrorInterface
     */
    private function compileDynamicPageView(DynamicPageView $pageView): void
    {
        $contentItems = $pageView->getCollectableItems();
        $template = $this->createTwigTemplate($pageView);

        foreach ($contentItems as &$contentItem) {
            if ($contentItem->isDraft() && !Service::hasRunTimeFlag(RuntimeStatus::USING_DRAFTS)) {
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
     * @since 0.1.1
     *
     * @throws TemplateErrorInterface
     */
    private function compileRepeaterPageView(RepeaterPageView &$pageView): void
    {
        $template = $this->createTwigTemplate($pageView);
        $permalinks = $pageView->getRepeaterPermalinks();

        foreach ($permalinks as $permalink) {
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
    private function writeToFilesystem($targetFile, $output, $fileType): void
    {
        $this->logger->notice('Writing {type} PageView file: {file}', [
            'type' => $fileType,
            'file' => $targetFile,
        ]);
        $this->folder->writeFile($targetFile, $output);
    }

    //
    // Redirect handling
    //

    /**
     * Write redirects for standard redirects.
     *
     * @throws TemplateErrorInterface
     *
     * @since 0.1.1
     */
    private function compileStandardRedirects(PermalinkDocument &$pageView): void
    {
        $redirects = $pageView->getRedirects();

        foreach ($redirects as $redirect) {
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
            $this->eventDispatcher->dispatch($redirectEvent, RedirectPreOutput::NAME);

            $this->compileStaticPageView($redirectPageView);
        }
    }

    /**
     * Write redirects for expanded redirects.
     *
     * @since 0.1.1
     */
    private function compileExpandedRedirects(RepeaterPageView &$pageView): void
    {
        $permalinks = $pageView->getRepeaterPermalinks();

        /** @var ExpandedValue[] $repeaterRedirect */
        foreach ($pageView->getRepeaterRedirects() as $repeaterRedirect) {
            /** @var ExpandedValue $redirect */
            foreach ($repeaterRedirect as $index => $redirect) {
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
                $this->eventDispatcher->dispatch($redirectEvent, RedirectPreOutput::NAME);

                $this->compilePageView($redirectPageView);
            }
        }
    }

    //
    // Twig Functionality
    //

    /**
     * Get the compiled HTML for a specific iteration of a repeater PageView.
     *
     * @since  0.1.1
     */
    private function buildRepeaterPageViewHTML(TemplateInterface &$template, RepeaterPageView &$pageView, ExpandedValue &$expandedValue): string
    {
        $defaultContext = [
            'this' => $pageView->createJail(),
        ];

        $pageView->evaluateFrontMatter([
            'permalink' => $expandedValue->getEvaluated(),
            'iterators' => $expandedValue->getIterators(),
        ]);

        $preEvent = new CompilerPreRenderRepeaterPageView($pageView, $expandedValue);
        $this->eventDispatcher->dispatch($preEvent, CompilerPreRenderRepeaterPageView::NAME);

        $context = array_merge($preEvent->getCustomVariables(), $defaultContext);
        $output = $template
            ->render($context)
        ;

        $postEvent = new CompilerPostRenderRepeaterPageView($pageView, $expandedValue, $output);
        $this->eventDispatcher->dispatch($postEvent, CompilerPostRenderRepeaterPageView::NAME);

        return $postEvent->getCompiledOutput();
    }

    /**
     * Get the compiled HTML for a specific ContentItem.
     *
     * @since  0.1.1
     */
    private function buildDynamicPageViewHTML(TemplateInterface &$template, CollectableItem|TemplateReadyDocument &$twigItem): string
    {
        $defaultContext = [
            'this' => $twigItem->createJail(),
        ];

        $preEvent = new CompilerPreRenderDynamicPageView($twigItem);
        $this->eventDispatcher->dispatch($preEvent, CompilerPreRenderDynamicPageView::NAME);

        $context = array_merge($preEvent->getCustomVariables(), $defaultContext);
        $output = $template
            ->render($context)
        ;

        $postEvent = new CompilerPostRenderDynamicPageView($twigItem, $output);
        $this->eventDispatcher->dispatch($postEvent, CompilerPostRenderDynamicPageView::NAME);

        return $postEvent->getCompiledOutput();
    }

    /**
     * Get the compiled HTML for a static PageView.
     *
     * @since  0.1.1
     *
     * @throws TemplateErrorInterface
     */
    private function buildStaticPageViewHTML(StaticPageView &$pageView): string
    {
        $defaultContext = [
            'this' => $pageView->createJail(),
        ];

        $preEvent = new CompilerPreRenderStaticPageView($pageView);
        $this->eventDispatcher->dispatch($preEvent, CompilerPreRenderStaticPageView::NAME);

        $context = array_merge($preEvent->getCustomVariables(), $defaultContext);
        $output = $this
            ->createTwigTemplate($pageView)
            ->render($context)
        ;

        $postEvent = new CompilerPostRenderStaticPageView($pageView, $output);
        $this->eventDispatcher->dispatch($postEvent, CompilerPostRenderStaticPageView::NAME);

        return $postEvent->getCompiledOutput();
    }

    /**
     * Create a Twig template that just needs an array to render.
     *
     * @since  0.1.1
     *
     * @throws TemplateErrorInterface
     */
    private function createTwigTemplate(BasePageView &$pageView): TemplateInterface
    {
        try {
            $template = $this->templateBridge->createTemplate($pageView->getContent());

            $this->templateMapping[$template->getTemplateName()] = $pageView->getRelativeFilePath();

            $event = new CompilerTemplateCreation($pageView, $template, $this->theme);
            $this->eventDispatcher->dispatch($event, CompilerTemplateCreation::NAME);

            return $template;
        } catch (TemplateErrorInterface $e) {
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
