<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use Exception;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use Symfony\Component\Yaml\Yaml;

abstract class BasePageView extends PermalinkFrontMatterDocument implements PermalinkDocument
{
    use TemplateEngineDependent;

    public const REPEATER_TYPE = 'repeater';

    public const DYNAMIC_TYPE = 'dynamic';

    public const STATIC_TYPE = 'static';

    protected $type;

    /**
     * Returns the type of PageView.
     */
    public function getType(): string
    {
        return $this->type;
    }

    //
    // Static utilities
    //

    /**
     * Create the appropriate object type when parsing a PageView.
     *
     * @return DynamicPageView|RepeaterPageView|StaticPageView
     */
    public static function create(File $filePath, array $complexVariables = [])
    {
        $instance = new StaticPageView($filePath);

        if (isset($instance->getRawFrontMatter()['collection'])
            || isset($instance->getRawFrontMatter()['dataset'])
        ) {
            return new DynamicPageView($filePath);
        }

        $instance->evaluateFrontMatter([], $complexVariables);

        if ($instance->hasExpandedFrontMatter()) {
            return new RepeaterPageView($filePath);
        }

        return $instance;
    }

    //
    // Virtual PageViews
    //

    /**
     * Create a virtual PageView.
     *
     * @param array  $frontMatter The Front Matter that this virtual PageView will have
     * @param string $body        The body of the virtual PageView
     */
    public static function createVirtual($frontMatter, $body): StaticPageView
    {
        if (vfsStreamWrapper::getRoot() == null) {
            vfsStream::setup();
        }

        $redirectFile = vfsStream::newFile(sprintf('redirect_%s.html.twig', uniqid()));
        $redirectFile
            ->setContent(sprintf(self::TEMPLATE, Yaml::dump($frontMatter, 2), $body))
            ->at(vfsStreamWrapper::getRoot())
        ;

        $file = new File($redirectFile->url());

        return new StaticPageView($file);
    }

    /**
     * Create a virtual PageView to create redirect files.
     *
     * @param string      $redirectFrom     The URL that will be redirecting to the target location
     * @param string      $redirectTo       The URL of the destination
     * @param bool|string $redirectTemplate The path to the template
     *
     * @return StaticPageView A virtual PageView with the redirection template
     */
    public static function createRedirect($redirectFrom, $redirectTo, $redirectTemplate = false): StaticPageView
    {
        $frontMatter = [
            'permalink' => $redirectFrom,
            'redirect' => $redirectTo,
            'menu' => false,
        ];

        $contentItemBody = fs::getInternalResource('redirect.html.twig');

        try {
            if (!empty($redirectTemplate)) {
                $redirectView = new File($redirectTemplate);
                $contentItemBody = $redirectView->getContents();
            }
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
        }

        return self::createVirtual($frontMatter, $contentItemBody);
    }
}
