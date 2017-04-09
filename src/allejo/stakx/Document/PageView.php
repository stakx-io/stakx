<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\FrontMatter\Document;
use allejo\stakx\System\Filesystem;
use allejo\stakx\System\StakxResource;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use Symfony\Component\Yaml\Yaml;

class PageView extends Document
{
    const REPEATER_TYPE = 'repeater';
    const DYNAMIC_TYPE = 'dynamic';
    const STATIC_TYPE = 'static';

    /**
     * @var Filesystem
     */
    private static $fileSys;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var PageView[]
     */
    private $children;

    /**
     * @var JailedDocument
     */
    private $jailInstance;

    /**
     * {@inheritdoc}
     */
    public function __construct($filePath)
    {
        parent::__construct($filePath);

        $this->children = array();
        $this->type = self::STATIC_TYPE;
    }

    //
    // Twig Jail
    // =========

    /**
     * {@inheritdoc}
     */
    public function createJail()
    {
        if (is_null($this->jailInstance))
        {
            $this->jailInstance = (new JailedDocument($this, array_merge(self::$whiteListFunctions, array(
                'getUrl',
            )), array('getChildren' => 'getJailedChildren')));
        }

        return $this->jailInstance;
    }

    public function getJailedChildren()
    {
        $children = $this->children;

        foreach ($children as &$child)
        {
            $child = $child->createJail();
        }

        return $children;
    }

    //
    // Getters
    // =======

    /**
     * Get child PageViews.
     *
     * A child is defined as a static PageView whose URL has a parent. For example, a PageView with a URL of
     * `/gallery/france/` would have the PageView whose URL is `/gallery` as a parent.
     *
     * @return PageView[]
     */
    public function &getChildren()
    {
        return $this->children;
    }

    /**
     * @return string Twig body
     */
    public function getContent()
    {
        return $this->bodyContent;
    }

    /**
     * Returns the type of the PageView.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * A fallback for the site menus that use the `url` field.
     *
     * @deprecated 0.1.0
     *
     * @todo       Remove this in the next major release
     */
    public function getUrl()
    {
        return $this->getPermalink();
    }

    //
    // Factory
    // =======

    /**
     * Create the appropriate object type when parsing a PageView.
     *
     * @param string $filePath The path to the file that will be parsed into a PageView
     *
     * @return DynamicPageView|PageView|RepeaterPageView
     */
    public static function create($filePath)
    {
        $instance = new self($filePath);

        if (isset($instance->getFrontMatter(false)['collection']))
        {
            return new DynamicPageView($filePath);
        }

        $instance->getFrontMatter();

        if ($instance->hasExpandedFrontMatter())
        {
            return new RepeaterPageView($filePath);
        }

        return $instance;
    }

    //
    // Virtual PageViews
    // =================

    /**
     * Create a virtual PageView.
     *
     * @param array  $frontMatter The Front Matter that this virtual PageView will have
     * @param string $body        The body of the virtual PageView
     *
     * @return PageView
     */
    public static function createVirtual($frontMatter, $body)
    {
        if (vfsStreamWrapper::getRoot() == null)
        {
            vfsStream::setup();
        }

        $redirectFile = vfsStream::newFile(sprintf('redirect_%s.html.twig', uniqid()));
        $redirectFile
            ->setContent(sprintf(self::TEMPLATE, Yaml::dump($frontMatter, 2), $body))
            ->at(vfsStreamWrapper::getRoot());

        return new self($redirectFile->url());
    }

    /**
     * Create a virtual PageView to create redirect files.
     *
     * @param string      $redirectFrom     The URL that will be redirecting to the target location
     * @param string      $redirectTo       The URL of the destination
     * @param string|bool $redirectTemplate The path to the template
     *
     * @return PageView A virtual PageView with the redirection template
     */
    public static function createRedirect($redirectFrom, $redirectTo, $redirectTemplate = false)
    {
        if (is_null(self::$fileSys))
        {
            self::$fileSys = new Filesystem();
        }

        $frontMatter = array(
            'permalink' => $redirectFrom,
            'redirect'  => $redirectTo,
            'menu'      => false,
        );

        if (!$redirectTemplate || !self::$fileSys->exists(self::$fileSys->absolutePath($redirectTemplate)))
        {
            $contentItemBody = StakxResource::getResource('redirect.html.twig');
        }
        else
        {
            $contentItemBody = file_get_contents(self::$fileSys->absolutePath($redirectTemplate));
        }

        return self::createVirtual($frontMatter, $contentItemBody);
    }
}
