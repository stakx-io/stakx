<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

use allejo\stakx\Command\BuildableCommand;
use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\ContentItem;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\PermalinkDocument;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Document\TemplateReadyDocument;
use allejo\stakx\Exception\FileAwareException;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Filesystem\FilesystemPath;
use allejo\stakx\FrontMatter\ExpandedValue;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Manager\ThemeManager;
use allejo\stakx\Filesystem\Folder;
use allejo\stakx\System\FilePath;
use allejo\stakx\Templating\TemplateBridgeInterface;
use allejo\stakx\Templating\TemplateErrorInterface;
use allejo\stakx\Templating\TemplateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * This class takes care of rendering the Twig body of PageViews with the respective information and it also takes care
 * of writing the rendered Twig to the filesystem.
 *
 * @internal
 *
 * @since 0.1.1
 */
class Compiler
{
    /** @var string|false */
    private $redirectTemplate;

    /** @var BasePageView[][] */
    private $importDependencies;

    /**
     * Any time a PageView extends another template, that relationship is stored in this array. This is necessary so
     * when watching a website, we can rebuild the necessary PageViews when these base templates change.
     *
     * ```
     * array['_layouts/base.html.twig'] = &PageView;
     * ```
     *
     * @var TemplateInterface[]
     */
    private $templateDependencies;

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

    /** @var Folder */
    private $folder;

    /** @var string */
    private $theme;

    private $templateBridge;
    private $pageManager;
    private $eventDispatcher;
    private $configuration;

    public function __construct(TemplateBridgeInterface $templateBridge, Configuration $configuration, PageManager $pageManager, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger)
    {
        $this->templateBridge = $templateBridge;
        $this->theme = '';
        $this->pageManager = $pageManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->configuration = $configuration;

        $this->pageViewsFlattened = &$pageManager->getPageViewsFlattened();
        $this->redirectTemplate = $this->configuration->getRedirectTemplate();
    }

    /**
     * @param Folder $folder
     */
    public function setTargetFolder(Folder $folder)
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
    // Twig parent templates
    ///

    public function isImportDependency($filePath)
    {
        return isset($this->importDependencies[$filePath]);
    }

    /**
     * Check whether a given file path is used as a parent template by a PageView
     *
     * @param  string $filePath
     *
     * @return bool
     */
    public function isParentTemplate($filePath)
    {
        return isset($this->templateDependencies[$filePath]);
    }

    /**
     * Rebuild all of the PageViews that used a given template as a parent
     *
     * @param string $filePath The file path to the parent Twig template
     */
    public function refreshParent($filePath)
    {
        foreach ($this->templateDependencies[$filePath] as &$parentTemplate)
        {
            $this->compilePageView($parentTemplate);
        }
    }

    public function getTemplateMappings()
    {
        return $this->templateMapping;
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

    public function compileImportDependencies($filePath)
    {
        foreach ($this->importDependencies[$filePath] as &$dependent)
        {
            $this->compilePageView($dependent);
        }
    }

    public function compileSome(array $filter = array())
    {
        /** @var BasePageView $pageView */
        foreach ($this->pageViewsFlattened as &$pageView)
        {
            $ns = $filter['namespace'];

            if ($pageView->hasDependencyOnCollection($ns, $filter['dependency']) ||
                $pageView->hasDependencyOnCollection($ns, null)
            ) {
                $this->compilePageView($pageView);
            }
        }
    }

    /**
     * Compile an individual PageView from a given path.
     *
     * @param string $filePath
     *
     * @throws FileNotFoundException When the given file path isn't tracked by the Compiler.
     */
    public function compilePageViewFromPath($filePath)
    {
        if (!isset($this->pageViewsFlattened[$filePath]))
        {
            throw new FileNotFoundException(sprintf('The "%s" PageView is not being tracked by this compiler.', $filePath));
        }

        $this->compilePageView($this->pageViewsFlattened[$filePath]);
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
        $this->templateBridge->setGlobalVariable('__currentTemplate', $pageView->getAbsoluteFilePath());
        $this->logger->debug('Compiling {type} PageView: {pageview}', array(
            'pageview' => $pageView->getRelativeFilePath(),
            'type' => $pageView->getType()
        ));

        try
        {
            switch ($pageView->getType())
            {
                case BasePageView::STATIC_TYPE:
                    $this->compileStaticPageView($pageView);
                    $this->compileStandardRedirects($pageView);
                    break;

                case BasePageView::DYNAMIC_TYPE:
                    $this->compileDynamicPageViews($pageView);
                    $this->compileStandardRedirects($pageView);
                    break;

                case BasePageView::REPEATER_TYPE:
                    $this->compileRepeaterPageViews($pageView);
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
        $pageView->compile();

        $targetFile = $pageView->getTargetFile();
        $output = $this->renderStaticPageView($pageView);

        $this->logger->notice('Writing file: {file}', array('file' => $targetFile));
        $this->folder->writeFile($targetFile, $output);
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
    private function compileDynamicPageViews(DynamicPageView &$pageView)
    {
        $contentItems = $pageView->getCollectableItems();
        $template = $this->createTwigTemplate($pageView);

        foreach ($contentItems as &$contentItem)
        {
            if ($contentItem->isDraft() && !Service::getParameter(BuildableCommand::USE_DRAFTS))
            {
                $this->logger->debug('{file}: marked as a draft', array(
                    'file' => $contentItem->getRelativeFilePath()
                ));

                continue;
            }

            $targetFile = $contentItem->getTargetFile();
            $output = $this->renderDynamicPageView($template, $contentItem);

            $this->logger->notice('Writing file: {file}', array('file' => $targetFile));
            $this->folder->writeFile($targetFile, $output);

            $this->compileStandardRedirects($contentItem);
        }
    }

    /**
     * Write the compiled output for a repeater PageView.
     *
     * @param RepeaterPageView $pageView

     * @since 0.1.1
     *
     * @throws TemplateErrorInterface
     */
    private function compileRepeaterPageViews(RepeaterPageView &$pageView)
    {
        $pageView->rewindPermalink();

        $template = $this->createTwigTemplate($pageView);
        $permalinks = $pageView->getRepeaterPermalinks();

        foreach ($permalinks as $permalink)
        {
            $pageView->bumpPermalink();
            $targetFile = $pageView->getTargetFile();
            $output = $this->renderRepeaterPageView($template, $pageView, $permalink);

            $this->logger->notice('Writing repeater file: {file}', ['file' => $targetFile]);
            $this->folder->writeFile($targetFile, $output);
        }
    }

    /**
     * @deprecated
     *
     * @todo This function needs to be rewritten or removed. Something
     *
     * @param ContentItem $contentItem
     */
    public function compileContentItem(ContentItem &$contentItem)
    {
        $pageView = &$contentItem->getPageView();
        $template = $this->createTwigTemplate($pageView);

        $this->templateBridge->setGlobalVariable('__currentTemplate', $pageView->getAbsoluteFilePath());
        $contentItem->evaluateFrontMatter($pageView->getFrontMatter(false));

        $targetFile = $contentItem->getTargetFile();
        $output = $this->renderDynamicPageView($template, $contentItem);

        $this->logger->notice('Writing file: {file}', array('file' => $targetFile));
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
             * @var int           $index
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
    private function renderRepeaterPageView(TemplateInterface &$template, RepeaterPageView &$pageView, ExpandedValue &$expandedValue)
    {
        $pageView->evaluateFrontMatter([
            'permalink' => $expandedValue->getEvaluated(),
            'iterators' => $expandedValue->getIterators(),
        ]);

        return $template
            ->render(array(
                'this' => $pageView->createJail(),
            ));
    }

    /**
     * Get the compiled HTML for a specific ContentItem.
     *
     * @since  0.1.1
     *
     * @return string
     */
    private function renderDynamicPageView(TemplateInterface &$template, TemplateReadyDocument &$twigItem)
    {
        return $template
            ->render(array(
                'this' => $twigItem->createJail(),
            ));
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
    private function renderStaticPageView(StaticPageView &$pageView)
    {
        return $this
            ->createTwigTemplate($pageView)
            ->render(array(
                'this' => $pageView->createJail(),
            ));
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

            if (Service::getParameter(BuildableCommand::WATCHING))
            {
                // Keep track of import dependencies
                foreach ($pageView->getImportDependencies() as $dependency)
                {
                    $this->importDependencies[$dependency][$pageView->getBasename()] = &$pageView;
                }

                // Keep track of Twig extends'
                $parent = $template->getParentTemplate();

                while ($parent !== false)
                {
                    // Replace the '@theme' namespace in Twig with the path to the theme folder and create a FilesystemPath object from the given path
                    $path = str_replace('@theme', fs::appendPath(ThemeManager::THEME_FOLDER, $this->theme), $parent->getTemplateName());
                    $path = new FilesystemPath($path);

                    $this->templateDependencies[(string)$path][$pageView->getBasename()] = &$pageView;

                    $parent = $parent->getParentTemplate();
                }
            }

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
