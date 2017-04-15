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
use allejo\stakx\FrontMatter\ExpandedValue;
use allejo\stakx\Manager\BaseManager;
use allejo\stakx\Manager\ThemeManager;
use allejo\stakx\Manager\TwigManager;
use allejo\stakx\System\Folder;
use Twig_Environment;
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

    public function setThemeName($themeName)
    {
        $this->theme = $themeName;
    }

    ///
    // Twig parent templates
    ///

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

    public function compileSome($filter = array())
    {
        /** @var PageView $pageView */
        foreach ($this->pageViewsFlattened as &$pageView)
        {
            if ($pageView->hasTwigDependency($filter['namespace'], $filter['dependency']))
            {
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
    public function compilePageView(&$pageView)
    {
        $this->output->debug('Compiling {type} PageView: {pageview}', array(
            'pageview' => $pageView->getRelativeFilePath(),
            'type' => $pageView->getType()
        ));

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

    /**
     * Write the compiled output for a static PageView.
     *
     * @param PageView $pageView
     *
     * @since 0.1.1
     */
    private function compileStaticPageView(&$pageView)
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
    private function compileDynamicPageViews(&$pageView)
    {
        $contentItems = $pageView->getContentItems();
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
            $output = $this->renderDynamicPageView($template, $pageView, $contentItem);

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
    private function compileRepeaterPageViews(&$pageView)
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
    public function compileContentItem(&$contentItem)
    {
        $pageView = $contentItem->getPageView();
        $template = $this->createTwigTemplate($pageView);

        $contentItem->evaluateFrontMatter($pageView->getFrontMatter(false));

        $targetFile = $contentItem->getTargetFile();
        $output = $this->renderDynamicPageView($template, $pageView, $contentItem);

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
    private function compileStandardRedirects(&$pageView)
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
    private function compileExpandedRedirects(&$pageView)
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
    private function renderRepeaterPageView(&$template, &$pageView, &$expandedValue)
    {
        $this->twig->addGlobal('__currentTemplate', $pageView->getFilePath());

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
     * @param PageView      $pageView
     * @param ContentItem   $contentItem
     *
     * @since  0.1.1
     *
     * @return string
     */
    private function renderDynamicPageView(&$template, &$pageView, &$contentItem)
    {
        $this->twig->addGlobal('__currentTemplate', $pageView->getFilePath());

        return $template
            ->render(array(
                'this' => $contentItem->createJail(),
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
    private function renderStaticPageView(&$pageView)
    {
        $this->twig->addGlobal('__currentTemplate', $pageView->getFilePath());

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
    private function createTwigTemplate(&$pageView)
    {
        try
        {
            $template = $this->twig->createTemplate($pageView->getContent());

            $this->templateMapping[$template->getTemplateName()] = $pageView->getRelativeFilePath();

            if (Service::getParameter(BuildableCommand::WATCHING))
            {
                $parent = $template->getParent(array());

                while (false !== $parent)
                {
                    $path = str_replace('@theme', $this->fs->appendPath(ThemeManager::THEME_FOLDER, $this->theme), $parent->getTemplateName());
                    $this->templateDependencies[$path][$pageView->getName()] = &$pageView;

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
