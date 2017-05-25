<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

use allejo\stakx\Command\BuildableCommand;
use allejo\stakx\Document\ContentItem;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\PageView;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Document\TwigDocument;
use allejo\stakx\Exception\FileAwareException;
use allejo\stakx\FrontMatter\ExpandedValue;
use allejo\stakx\Manager\BaseManager;
use allejo\stakx\Manager\ThemeManager;
use allejo\stakx\Manager\TwigManager;
use allejo\stakx\System\Folder;
use allejo\stakx\System\FilePath;
use Twig_Environment;
use Twig_Error_Runtime;
use Twig_Error_Syntax;
use Twig_Source;
use Twig_Template;

/**
 * This class takes care of rendering the Twig body of PageViews with the respective information and it also takes care
 * of writing the rendered Twig to the filesystem.
 *
 * @internal
 *
 * @since 0.1.1
 */
class Compiler extends BaseManager
{
    /** @var string|false */
    private $redirectTemplate;

    /** @var PageView[][] */
    private $importDependencies;

    /** @var Twig_Template[] */
    private $templateDependencies;

    /** @var PageView[] */
    private $pageViewsFlattened;

    /** @var string[] */
    private $templateMapping;

    /** @var PageView[][] */
    private $pageViews;

    /** @var Folder */
    private $folder;

    /** @var string */
    private $theme;

    /** @var Twig_Environment */
    private $twig;

    public function __construct()
    {
        parent::__construct();

        $this->twig = TwigManager::getInstance();
        $this->theme = '';
    }

    /**
     * @param string|false $template
     */
    public function setRedirectTemplate($template)
    {
        $this->redirectTemplate = $template;
    }

    /**
     * @param Folder $folder
     */
    public function setTargetFolder(Folder $folder)
    {
        $this->folder = $folder;
    }

    /**
     * @param PageView[][] $pageViews
     * @param PageView[]   $pageViewsFlattened
     */
    public function setPageViews(array &$pageViews, array &$pageViewsFlattened)
    {
        $this->pageViews = &$pageViews;
        $this->pageViewsFlattened = &$pageViewsFlattened;
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
        /** @var PageView $pageView */
        foreach ($this->pageViewsFlattened as &$pageView)
        {
            $ns = $filter['namespace'];

            if ($pageView->hasTwigDependency($ns, $filter['dependency']) ||
                $pageView->hasTwigDependency($ns, null)
            ) {
                $this->compilePageView($pageView);
            }
        }
    }

    /**
     * Compile an individual PageView item.
     *
     * This function will take care of determining *how* to treat the PageView and write the compiled output to a the
     * respective target file.
     *
     * @param DynamicPageView|RepeaterPageView|PageView $pageView The PageView that needs to be compiled
     *
     * @since 0.1.1
     */
    public function compilePageView(PageView &$pageView)
    {
        $this->twig->addGlobal('__currentTemplate', $pageView->getFilePath());
        $this->output->debug('Compiling {type} PageView: {pageview}', array(
            'pageview' => $pageView->getRelativeFilePath(),
            'type' => $pageView->getType()
        ));

        try
        {
            switch ($pageView->getType())
            {
                case PageView::STATIC_TYPE:
                    $this->compileStaticPageView($pageView);
                    $this->compileStandardRedirects($pageView);
                    break;

                case PageView::DYNAMIC_TYPE:
                    $this->compileDynamicPageViews($pageView);
                    $this->compileStandardRedirects($pageView);
                    break;

                case PageView::REPEATER_TYPE:
                    $this->compileRepeaterPageViews($pageView);
                    $this->compileExpandedRedirects($pageView);
                    break;
            }
        }
        catch (Twig_Error_Runtime $e)
        {
            throw new FileAwareException(
                $e->getRawMessage(),
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
     * @param PageView $pageView
     *
     * @since 0.1.1
     */
    private function compileStaticPageView(PageView &$pageView)
    {
        $targetFile = $pageView->getTargetFile();
        $output = $this->renderStaticPageView($pageView);

        $this->output->notice('Writing file: {file}', array('file' => $targetFile));
        $this->folder->writeFile($targetFile, $output);
    }

    /**
     * Write the compiled output for a dynamic PageView.
     *
     * @param DynamicPageView $pageView
     *
     * @since 0.1.1
     */
    private function compileDynamicPageViews(DynamicPageView &$pageView)
    {
        $contentItems = $pageView->getRepeatableItems();
        $template = $this->createTwigTemplate($pageView);

        foreach ($contentItems as &$contentItem)
        {
            if ($contentItem->isDraft() && !Service::getParameter(BuildableCommand::USE_DRAFTS))
            {
                $this->output->debug('{file}: marked as a draft', array(
                    'file' => $contentItem->getRelativeFilePath()
                ));

                continue;
            }

            $targetFile = $contentItem->getTargetFile();
            $output = $this->renderDynamicPageView($template, $contentItem);

            $this->output->notice('Writing file: {file}', array('file' => $targetFile));
            $this->folder->writeFile($targetFile, $output);
        }
    }

    /**
     * Write the compiled output for a repeater PageView.
     *
     * @param RepeaterPageView $pageView
     *
     * @since 0.1.1
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

            $this->output->notice('Writing repeater file: {file}', array('file' => $targetFile));
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

        $this->twig->addGlobal('__currentTemplate', $pageView->getFilePath());
        $contentItem->evaluateFrontMatter($pageView->getFrontMatter(false));

        $targetFile = $contentItem->getTargetFile();
        $output = $this->renderDynamicPageView($template, $contentItem);

        $this->output->notice('Writing file: {file}', array('file' => $targetFile));
        $this->folder->writeFile($targetFile, $output);
    }

    ///
    // Redirect handling
    ///

    /**
     * Write redirects for standard redirects.
     *
     * @param PageView $pageView
     *
     * @since 0.1.1
     */
    private function compileStandardRedirects(PageView &$pageView)
    {
        $redirects = $pageView->getRedirects();

        foreach ($redirects as $redirect)
        {
            $redirectPageView = PageView::createRedirect(
                $redirect,
                $pageView->getPermalink(),
                $this->redirectTemplate
            );

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
                $redirectPageView = PageView::createRedirect(
                    $redirect->getEvaluated(),
                    $permalinks[$index]->getEvaluated(),
                    $this->redirectTemplate
                );
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
     * @param Twig_Template $template
     * @param PageView      $pageView
     * @param ExpandedValue $expandedValue
     *
     * @since  0.1.1
     *
     * @return string
     */
    private function renderRepeaterPageView(Twig_Template &$template, RepeaterPageView &$pageView, ExpandedValue &$expandedValue)
    {
        $pageView->setFrontMatter(array(
            'permalink' => $expandedValue->getEvaluated(),
            'iterators' => $expandedValue->getIterators(),
        ));

        return $template
            ->render(array(
                'this' => $pageView->createJail(),
            ));
    }

    /**
     * Get the compiled HTML for a specific ContentItem.
     *
     * @param Twig_Template $template
     * @param TwigDocument  $twigItem
     *
     * @return string
     * @since  0.1.1
     *
     */
    private function renderDynamicPageView(Twig_Template &$template, TwigDocument &$twigItem)
    {
        return $template
            ->render(array(
                'this' => $twigItem->createJail(),
            ));
    }

    /**
     * Get the compiled HTML for a static PageView.
     *
     * @param PageView $pageView
     *
     * @since  0.1.1
     *
     * @throws \Exception
     * @throws \Throwable
     * @throws Twig_Error_Syntax
     *
     * @return string
     */
    private function renderStaticPageView(PageView &$pageView)
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
     * @param PageView $pageView The PageView whose body will be used for Twig compilation
     *
     * @since  0.1.1
     *
     * @throws \Exception
     * @throws \Throwable
     * @throws Twig_Error_Syntax
     *
     * @return Twig_Template
     */
    private function createTwigTemplate(PageView &$pageView)
    {
        try
        {
            $template = $this->twig->createTemplate($pageView->getContent());

            $this->templateMapping[$template->getTemplateName()] = $pageView->getRelativeFilePath();

            if (Service::getParameter(BuildableCommand::WATCHING))
            {
                // Keep track of import dependencies
                foreach ($pageView->getImportDependencies() as $dependency)
                {
                    $this->importDependencies[$dependency][$pageView->getName()] = &$pageView;
                }

                // Keep track of Twig extends'
                $parent = $template->getParent(array());

                while ($parent !== false)
                {
                    // Replace the '@theme' namespace in Twig with the path to the theme folder and create a UnixFilePath object from the given path
                    $path = str_replace('@theme', $this->fs->appendPath(ThemeManager::THEME_FOLDER, $this->theme), $parent->getTemplateName());
                    $path = new FilePath($path);

                    $this->templateDependencies[(string)$path][$pageView->getName()] = &$pageView;

                    $parent = $parent->getParent(array());
                }
            }

            return $template;
        }
        catch (Twig_Error_Syntax $e)
        {
            $e->setTemplateLine($e->getTemplateLine() + $pageView->getLineOffset());
            $e->setSourceContext(new Twig_Source(
                $pageView->getContent(),
                $pageView->getName(),
                $pageView->getRelativeFilePath()
            ));

            throw $e;
        }
    }
}
