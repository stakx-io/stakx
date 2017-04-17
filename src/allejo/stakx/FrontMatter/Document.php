<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\FrontMatter;

use allejo\stakx\Document\JailedDocumentInterface;
use allejo\stakx\Exception\FileAwareException;
use allejo\stakx\Exception\InvalidSyntaxException;
use allejo\stakx\FrontMatter\Exception\YamlVariableUndefinedException;
use allejo\stakx\System\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

abstract class Document implements WritableDocumentInterface, JailedDocumentInterface, \ArrayAccess
{
    const TEMPLATE = "---\n%s\n---\n\n%s";

    /**
     * The names of FrontMatter keys that are specially defined for all Documents
     *
     * @var array
     */
    public static $specialFrontMatterKeys = array(
        'filename', 'basename'
    );

    protected static $whiteListFunctions = array(
        'getPermalink', 'getRedirects', 'getTargetFile', 'getName', 'getFilePath', 'getRelativeFilePath', 'getContent',
        'getExtension', 'getFrontMatter'
    );

    /**
     * An array to keep track of collection or data dependencies used inside of a Twig template.
     *
     * $dataDependencies['collections'] = array()
     * $dataDependencies['data'] = array()
     *
     * @var array
     */
    protected $dataDependencies;

    /**
     * FrontMatter values that can be injected or set after the file has been parsed. Values in this array will take
     * precedence over values in $frontMatter.
     *
     * @var array
     */
    protected $writableFrontMatter;

    /**
     * A list of Front Matter values that should not be returned directly from the $frontMatter array. Values listed
     * here have dedicated functions that handle those Front Matter values and the respective functions should be called
     * instead.
     *
     * @var string[]
     */
    protected $frontMatterBlacklist;

    /**
     * Set to true if the front matter has already been evaluated with variable interpolation.
     *
     * @var bool
     */
    protected $frontMatterEvaluated;

    /**
     * @var Parser
     */
    protected $frontMatterParser;

    /**
     * An array containing the Yaml of the file.
     *
     * @var array
     */
    protected $frontMatter;

    /**
     * Set to true if the body has already been parsed as markdown or any other format.
     *
     * @var bool
     */
    protected $bodyContentEvaluated;

    /**
     * Only the body of the file, i.e. the content.
     *
     * @var string
     */
    protected $bodyContent;

    /**
     * The permalink for this object.
     *
     * @var string
     */
    protected $permalink;

    /**
     * A filesystem object.
     *
     * @var Filesystem
     */
    protected $fs;

    /**
     * The extension of the file.
     *
     * @var string
     */
    private $extension;

    /**
     * The number of lines that Twig template errors should offset.
     *
     * @var int
     */
    private $lineOffset;

    /**
     * A list URLs that will redirect to this object.
     *
     * @var string[]
     */
    private $redirects;

    /**
     * The original file path to the ContentItem.
     *
     * @var SplFileInfo
     */
    private $filePath;

    /**
     * ContentItem constructor.
     *
     * @param string $filePath The path to the file that will be parsed into a ContentItem
     *
     * @throws FileNotFoundException The given file path does not exist
     * @throws IOException           The file was not a valid ContentItem. This would meam there was no front matter or
     *                               no body
     */
    public function __construct($filePath)
    {
        $this->frontMatterBlacklist = array('permalink', 'redirects');
        $this->writableFrontMatter = array();

        $this->filePath = $filePath;
        $this->fs = new Filesystem();

        if (!$this->fs->exists($filePath))
        {
            throw new FileNotFoundException("The following file could not be found: ${filePath}");
        }

        $this->extension = strtolower($this->fs->getExtension($filePath));

        $this->refreshFileContent();
    }

    /**
     * Return the body of the Content Item.
     *
     * @return string
     */
    abstract public function getContent();

    /**
     * Get the extension of the current file.
     *
     * @return string
     */
    final public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Get the original file path.
     *
     * @return string
     */
    final public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * The number of lines that are taken up by FrontMatter and white space.
     *
     * @return int
     */
    final public function getLineOffset()
    {
        return $this->lineOffset;
    }

    /**
     * Get the name of the item, which is just the filename without the extension.
     *
     * @return string
     */
    final public function getName()
    {
        return $this->fs->getBaseName($this->filePath);
    }

    /**
     * Get the filename of this document.
     *
     * @return string
     */
    final public function getFileName()
    {
        return $this->fs->getFileName($this->filePath);
    }

    /**
     * Get the relative path to this file relative to the root of the Stakx website.
     *
     * @return string
     */
    final public function getRelativeFilePath()
    {
        if ($this->filePath instanceof SplFileInfo)
        {
            return $this->filePath->getRelativePathname();
        }

        // TODO ensure that we always get SplFileInfo objects, even when handling VFS documents
        return $this->fs->getRelativePath($this->filePath);
    }

    /**
     * Get the destination of where this Content Item would be written to when the website is compiled.
     *
     * @return string
     */
    final public function getTargetFile()
    {
        $permalink = $this->getPermalink();
        $missingFile = (substr($permalink, -1) == '/');
        $permalink = str_replace('/', DIRECTORY_SEPARATOR, $permalink);

        if ($missingFile)
        {
            $permalink = rtrim($permalink, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';
        }

        return ltrim($permalink, DIRECTORY_SEPARATOR);
    }

    /**
     * Check whether this object has a reference to a collection or data item.
     *
     * @param string $namespace 'collections' or 'data'
     * @param string $needle
     *
     * @return bool
     */
    final public function hasTwigDependency($namespace, $needle)
    {
        return in_array($needle, $this->dataDependencies[$namespace]);
    }

    /**
     * Read the file, and parse its contents.
     */
    final public function refreshFileContent()
    {
        // This function can be called after the initial object was created and the file may have been deleted since the
        // creation of the object.
        if (!$this->fs->exists($this->filePath))
        {
            throw new FileNotFoundException(null, 0, null, $this->filePath);
        }

        // $fileStructure[1] is the YAML
        // $fileStructure[2] is the amount of new lines after the closing `---` and the beginning of content
        // $fileStructure[3] is the body of the document
        $fileStructure = array();

        $rawFileContents = file_get_contents($this->filePath);
        preg_match('/---\R(.*?\R)?---(\s+)(.*)/s', $rawFileContents, $fileStructure);

        if (count($fileStructure) != 4)
        {
            throw new InvalidSyntaxException('Invalid FrontMatter file', 0, null, $this->getRelativeFilePath());
        }

        if (empty(trim($fileStructure[3])))
        {
            throw new InvalidSyntaxException('FrontMatter files must have a body to render', 0, null, $this->getRelativeFilePath());
        }

        // The hard coded 1 is the offset used to count the new line used after the first `---` that is not caught in the regex
        $this->lineOffset = substr_count($fileStructure[1], "\n") + substr_count($fileStructure[2], "\n") + 1;
        $this->bodyContent = $fileStructure[3];

        if (!empty(trim($fileStructure[1])))
        {
            $this->frontMatter = Yaml::parse($fileStructure[1], Yaml::PARSE_DATETIME);

            if (!empty($this->frontMatter) && !is_array($this->frontMatter))
            {
                throw new ParseException('The evaluated FrontMatter should be an array');
            }
        }
        else
        {
            $this->frontMatter = array();
        }

        $this->frontMatterEvaluated = false;
        $this->bodyContentEvaluated = false;
        $this->permalink = null;

        $this->findTwigDataDependencies('collections');
        $this->findTwigDataDependencies('data');
    }

    /**
     * Get all of the references to either DataItems or ContentItems inside of given string.
     *
     * @param string $filter 'collections' or 'data'
     */
    private function findTwigDataDependencies($filter)
    {
        $regex = '/{[{%](?:.+)?(?:' . $filter . ')(?:\.|\[\')(\w+)(?:\'\])?.+[%}]}/';
        $results = array();

        preg_match_all($regex, $this->bodyContent, $results);

        $this->dataDependencies[$filter] = array_unique($results[1]);
    }

    //
    // Permalink and redirect functionality
    //

    /**
     * Get the permalink of this Content Item.
     *
     * @throws \Exception
     *
     * @return string
     */
    final public function getPermalink()
    {
        if (!is_null($this->permalink))
        {
            return $this->permalink;
        }

        if (!is_null($this->frontMatterParser) && $this->frontMatterParser->hasExpansion())
        {
            throw new \Exception('The permalink for this item has not been set');
        }

        $permalink = (is_array($this->frontMatter) && isset($this->frontMatter['permalink'])) ?
            $this->frontMatter['permalink'] : $this->getPathPermalink();

        if (is_array($permalink))
        {
            $this->permalink = $permalink[0];
            array_shift($permalink);
            $this->redirects = $permalink;
        }
        else
        {
            $this->permalink = $permalink;
            $this->redirects = array();
        }

        $this->permalink = $this->sanitizePermalink($this->permalink);
        $this->permalink = str_replace(DIRECTORY_SEPARATOR, '/', $this->permalink);
        $this->permalink = '/' . ltrim($this->permalink, '/'); // Permalinks should always use '/' and not be OS specific

        return $this->permalink;
    }

    /**
     * Get an array of URLs that will redirect to.
     *
     * @return string[]
     */
    final public function getRedirects()
    {
        if (is_null($this->redirects))
        {
            $this->getPermalink();
        }

        return $this->redirects;
    }

    /**
     * Get the permalink based off the location of where the file is relative to the website. This permalink is to be
     * used as a fallback in the case that a permalink is not explicitly specified in the Front Matter.
     *
     * @return string
     */
    private function getPathPermalink()
    {
        // Remove the protocol of the path, if there is one and prepend a '/' to the beginning
        $cleanPath = preg_replace('/[\w|\d]+:\/\//', '', $this->getRelativeFilePath());
        $cleanPath = ltrim($cleanPath, DIRECTORY_SEPARATOR);

        // Handle vfs:// paths by replacing their forward slashes with the OS appropriate directory separator
        if (DIRECTORY_SEPARATOR !== '/')
        {
            $cleanPath = str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
        }

        // Check the first folder and see if it's a data folder (starts with an underscore) intended for stakx
        $folders = explode(DIRECTORY_SEPARATOR, $cleanPath);

        if (substr($folders[0], 0, 1) === '_')
        {
            array_shift($folders);
        }

        $cleanPath = implode(DIRECTORY_SEPARATOR, $folders);

        return $cleanPath;
    }

    /**
     * Sanitize a permalink to remove unsupported characters or multiple '/' and replace spaces with hyphens.
     *
     * @param string $permalink A permalink
     *
     * @return string $permalink The sanitized permalink
     */
    private function sanitizePermalink($permalink)
    {
        // Remove multiple '/' together
        $permalink = preg_replace('/\/+/', '/', $permalink);

        // Replace all spaces with hyphens
        $permalink = str_replace(' ', '-', $permalink);

        // Remove all disallowed characters
        $permalink = preg_replace('/[^0-9a-zA-Z-_\/\\\.]/', '', $permalink);

        // Handle unnecessary extensions
        $extensionsToStrip = array('twig');

        if (in_array($this->fs->getExtension($permalink), $extensionsToStrip))
        {
            $permalink = $this->fs->removeExtension($permalink);
        }

        // Remove any special characters before a sane value
        $permalink = preg_replace('/^[^0-9a-zA-Z-_]*/', '', $permalink);

        // Convert permalinks to lower case
        $permalink = mb_strtolower($permalink, 'UTF-8');

        return $permalink;
    }

    //
    // WritableFrontMatter Implementation
    //

    /**
     * {@inheritdoc}
     */
    final public function evaluateFrontMatter($variables = null)
    {
        if (!is_null($variables))
        {
            $this->frontMatter = array_merge($this->frontMatter, $variables);
            $this->evaluateYaml($this->frontMatter);
        }
    }

    /**
     * {@inheritdoc}
     */
    final public function getFrontMatter($evaluateYaml = true)
    {
        if (is_null($this->frontMatter))
        {
            $this->frontMatter = array();
        }
        elseif (!$this->frontMatterEvaluated && $evaluateYaml)
        {
            $this->evaluateYaml($this->frontMatter);
        }

        return $this->frontMatter;
    }

    /**
     * {@inheritdoc}
     */
    final public function hasExpandedFrontMatter()
    {
        return !is_null($this->frontMatterParser) && $this->frontMatterParser->hasExpansion();
    }

    /**
     * {@inheritdoc.
     */
    final public function appendFrontMatter(array $frontMatter)
    {
        foreach ($frontMatter as $key => $value)
        {
            $this->writableFrontMatter[$key] = $value;
        }
    }

    /**
     * {@inheritdoc.
     */
    final public function deleteFrontMatter($key)
    {
        if (!isset($this->writableFrontMatter[$key]))
        {
            return;
        }

        unset($this->writableFrontMatter[$key]);
    }

    /**
     * {@inheritdoc.
     */
    final public function setFrontMatter(array $frontMatter)
    {
        if (!is_array($frontMatter))
        {
            throw new \InvalidArgumentException('An array is required for setting the writable FrontMatter');
        }

        $this->writableFrontMatter = $frontMatter;
    }

    /**
     * Evaluate an array of data for FrontMatter variables. This function will modify the array in place.
     *
     * @param array $yaml An array of data containing FrontMatter variables
     *
     * @throws YamlVariableUndefinedException A FrontMatter variable used does not exist
     */
    private function evaluateYaml(&$yaml)
    {
        try
        {
            $this->frontMatterParser = new Parser($yaml, array(
                'filename' => $this->getFileName(),
                'basename' => $this->getName(),
            ));
            $this->frontMatterEvaluated = true;
        }
        catch (\Exception $e)
        {
            throw FileAwareException::castException($e, $this->getRelativeFilePath());
        }
    }

    //
    // ArrayAccess Implementation
    //

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset))
        {
            throw new \InvalidArgumentException('$offset cannot be null');
        }

        $this->writableFrontMatter[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        if (isset($this->writableFrontMatter[$offset]) || isset($this->frontMatter[$offset]))
        {
            return true;
        }

        $fxnCall = 'get' . ucfirst($offset);

        return method_exists($this, $fxnCall) && in_array($fxnCall, static::$whiteListFunctions);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->writableFrontMatter[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $fxnCall = 'get' . ucfirst($offset);

        if (in_array($fxnCall, self::$whiteListFunctions) && method_exists($this, $fxnCall))
        {
            return call_user_func_array(array($this, $fxnCall), array());
        }

        if (isset($this->writableFrontMatter[$offset]))
        {
            return $this->writableFrontMatter[$offset];
        }

        if (isset($this->frontMatter[$offset]))
        {
            return $this->frontMatter[$offset];
        }

        return null;
    }
}
