<?php

namespace allejo\stakx\Object;

use allejo\stakx\FrontMatter\FrontMatterParser;
use allejo\stakx\FrontMatter\YamlVariableUndefinedException;
use allejo\stakx\System\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Yaml\Yaml;

abstract class FrontMatterObject
{
    /**
     * An array to keep track of collection or data dependencies used inside of a Twig template
     *
     * $dataDependencies['collections'] = array()
     * $dataDependencies['data'] = array()
     *
     * @var array
     */
    protected $dataDependencies;

    /**
     * FrontMatter values that can be injected or set after the file has been parsed. Values in this array will take
     * precedence over values in $frontMatter
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
     * Set to true if the front matter has already been evaluated with variable interpolation
     *
     * @var bool
     */
    protected $frontMatterEvaluated;

    /**
     * @var FrontMatterParser
     */
    protected $frontMatterParser;

    /**
     * An array containing the Yaml of the file
     *
     * @var array
     */
    protected $frontMatter;

    /**
     * Set to true if the body has already been parsed as markdown or any other format
     *
     * @var bool
     */
    protected $bodyContentEvaluated;

    /**
     * Only the body of the file, i.e. the content
     *
     * @var string
     */
    protected $bodyContent;

    /**
     * The extension of the file
     *
     * @var string
     */
    protected $extension;

    /**
     * The original file path to the ContentItem
     *
     * @var string
     */
    protected $filePath;

    /**
     * The permalink for this object
     *
     * @var string
     */
    protected $permalink;

    /**
     * When set to true, only certain functions will be allowed to be called. This is to allow limited functionality
     * from within Twig templates
     *
     * @var bool
     */
    protected $protectedMode;

    /**
     * A filesystem object
     *
     * @var Filesystem
     */
    protected $fs;

    /**
     * A list URLs that will redirect to this object
     *
     * @var string[]
     */
    private $redirects;

    /**
     * ContentItem constructor.
     *
     * @param string $filePath The path to the file that will be parsed into a ContentItem
     *
     * @throws FileNotFoundException The given file path does not exist
     * @throws IOException           The file was not a valid ContentItem. This would meam there was no front matter or
     *                               no body
     */
    public function __construct ($filePath)
    {
        $this->frontMatterBlacklist = array('permalink', 'redirects');
        $this->writableFrontMatter = array();

        $this->filePath  = $filePath;
        $this->fs        = new Filesystem();

        if (!$this->fs->exists($filePath))
        {
            throw new FileNotFoundException("The following file could not be found: ${filePath}");
        }

        $this->extension = strtolower($this->fs->getExtension($filePath));

        $this->refreshFileContent();
    }

    /**
     * The magic getter returns values from the front matter in order to make these values accessible to Twig templates
     * in a simple fashion
     *
     * @param  string $name The key in the front matter
     *
     * @return mixed|null
     */
    public function __get ($name)
    {
        if (isset($this->writableFrontMatter[$name]))
        {
            return $this->writableFrontMatter[$name];
        }

        return (isset($this->frontMatter[$name]) ? $this->frontMatter[$name] : null);
    }

    /**
     * The magic getter returns true if the value exists in the Front Matter. This is used in conjunction with the __get
     * function
     *
     * @param  string $name The name of the Front Matter value being looked for
     *
     * @return bool
     */
    public function __isset ($name)
    {
        return (
            !in_array($name, $this->frontMatterBlacklist)) &&
            (isset($this->frontMatter[$name]) || isset($this->writableFrontMatter[$name])
        );
    }

    /**
     * Return the body of the Content Item
     *
     * @return string
     */
    abstract public function getContent ();

    /**
     * @param array|null $variables An array of YAML variables to use in evaluating the `$permalink` value
     */
    final public function evaluateFrontMatter ($variables = null)
    {
        if (!is_null($variables))
        {
            $this->frontMatter = array_merge($this->frontMatter, $variables);
            $this->handleSpecialFrontMatter();
            $this->evaluateYaml($this->frontMatter);
        }
    }

    /**
     * Get the Front Matter of a ContentItem as an array
     *
     * @param  bool $evaluateYaml When set to true, the YAML will be evaluated for variables
     *
     * @return array
     */
    final public function getFrontMatter ($evaluateYaml = true)
    {
        if (is_null($this->frontMatter))
        {
            $this->frontMatter = array();
        }
        else if (!$this->frontMatterEvaluated && $evaluateYaml)
        {
            $this->evaluateYaml($this->frontMatter);
            $this->frontMatterEvaluated = true;
        }

        return $this->frontMatter;
    }

    /**
     * Get the permalink of this Content Item
     *
     * @return string
     * @throws \Exception
     */
    final public function getPermalink ()
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

        return $this->permalink;
    }

    /**
     * Get an array of URLs that will redirect to
     *
     * @return string[]
     */
    final public function getRedirects ()
    {
        if (is_null($this->redirects))
        {
            $this->getPermalink();
        }

        return $this->redirects;
    }

    /**
     * Get the destination of where this Content Item would be written to when the website is compiled
     *
     * @return string
     */
    final public function getTargetFile ()
    {
        $permalink = $this->getPermalink();
        $extension = $this->fs->getExtension($permalink);
        $permalink = str_replace('/', DIRECTORY_SEPARATOR, $permalink);

        if (empty($extension))
        {
            $permalink = rtrim($permalink, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';
        }

        return ltrim($permalink, DIRECTORY_SEPARATOR);
    }

    /**
     * Get the name of the item, which is just the file name without the extension
     *
     * @return string
     */
    final public function getName ()
    {
        return $this->fs->getBaseName($this->filePath);
    }

    /**
     * Get the original file path
     *
     * @return string
     */
    final public function getFilePath ()
    {
        return $this->filePath;
    }

    /**
     * Get the relative path to this file relative to the root of the Stakx website
     *
     * @return string
     */
    final public function getRelativeFilePath ()
    {
        return $this->fs->getRelativePath($this->filePath);
    }

    /**
     * Returns true when the evaluated Front Matter has expanded values embeded
     *
     * @return bool
     */
    final public function hasExpandedFrontMatter ()
    {
        return (!is_null($this->frontMatterParser) && $this->frontMatterParser->hasExpansion());
    }

    /**
     * Read the file, and parse its contents
     */
    final public function refreshFileContent ()
    {
        $rawFileContents = file_get_contents($this->filePath);

        $frontMatter = array();
        preg_match('/---(.*?)---(.*)/s', $rawFileContents, $frontMatter);

        if (count($frontMatter) != 3)
        {
            throw new IOException(sprintf("'%s' is not a valid ContentItem",
                    $this->fs->getFileName($this->filePath))
            );
        }

        if (empty(trim($frontMatter[2])))
        {
            throw new IOException(sprintf('A ContentItem (%s) must have a body to render',
                    $this->fs->getFileName($this->filePath))
            );
        }

        $this->frontMatter = Yaml::parse($frontMatter[1]);
        $this->bodyContent = trim($frontMatter[2]);

        $this->frontMatterEvaluated = false;
        $this->bodyContentEvaluated = false;
        $this->permalink = null;

        $this->handleSpecialFrontMatter();
        $this->findTwigDataDependencies('collections');
        $this->findTwigDataDependencies('data');
    }

    /**
     * Check whether this object has a reference to a collection or data item
     *
     * @param  string $namespace 'collections' or 'data'
     * @param  string $needle
     *
     * @return bool
     */
    final public function hasTwigDependency ($namespace, $needle)
    {
        return (in_array($needle, $this->dataDependencies[$namespace]));
    }

    /**
     * Append a custom FrontMatter value
     *
     * @param array $frontMatter
     */
    final public function appendFrontMatter (array $frontMatter)
    {
        foreach ($frontMatter as $key => $value)
        {
            $this->writableFrontMatter[$key] = $value;
        }
    }

    /**
     * Delete a custom FrontMatter value
     *
     * This will not delete a FrontMatter value parsed from the file
     *
     * @param string $key
     */
    final public function deleteFrontMatter ($key)
    {
        if (!isset($this->writableFrontMatter[$key])) { return; }

        unset($this->writableFrontMatter[$key]);
    }

    /**
     * Set custom FrontMatter values
     *
     * These custom values are temporary and will take precedence over Front Matter evaluated from the file but is only
     * available to Twig templates
     *
     * @param array $frontMatter
     */
    final public function setFrontMatter (array $frontMatter)
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
     * @param  array $yaml An array of data containing FrontMatter variables
     *
     * @throws YamlVariableUndefinedException A FrontMatter variable used does not exist
     */
    final protected function evaluateYaml (&$yaml)
    {
        $this->frontMatterParser = new FrontMatterParser($yaml);
    }

    /**
     * Handle special front matter values that need special treatment or have special meaning to a Content Item
     */
    private function handleSpecialFrontMatter ()
    {
        if (isset($this->frontMatter['date']))
        {
            try
            {
                // Coming from a string variable
                $itemDate = new \DateTime($this->frontMatter['date']);
            }
            catch (\Exception $e)
            {
                // YAML has parsed them to Epoch time
                $itemDate = \DateTime::createFromFormat('U', $this->frontMatter['date']);
            }

            if (!$itemDate === false)
            {
                $this->frontMatter['year']  = $itemDate->format('Y');
                $this->frontMatter['month'] = $itemDate->format('m');
                $this->frontMatter['day']   = $itemDate->format('d');
            }
        }
    }

    /**
     * Get all of the references to either DataItems or ContentItems inside of given string
     *
     * @param string $filter 'collections' or 'data'
     */
    private function findTwigDataDependencies ($filter)
    {
        $regex = '/{[{%](?:.+)?(?:' . $filter . ')(?:\.|\[\')(\w+)(?:\'\])?.+[%}]}/';
        $results = array();

        preg_match_all($regex, $this->bodyContent, $results);

        $this->dataDependencies[$filter] = array_unique($results[1]);
    }

    /**
     * Get the permalink based off the location of where the file is relative to the website. This permalink is to be
     * used as a fallback in the case that a permalink is not explicitly specified in the Front Matter.
     *
     * @return string
     */
    private function getPathPermalink ()
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
     * Sanitize a permalink to remove unsupported characters or multiple '/' and replace spaces with hyphens
     *
     * @param  string $permalink A permalink
     *
     * @return string $permalink The sanitized permalink
     */
    private function sanitizePermalink ($permalink)
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

        // Remove a special './' combination from the beginning of a path
        if (substr($permalink, 0, 2) === '.' . DIRECTORY_SEPARATOR)
        {
            $permalink = substr($permalink, 2);
        }

        // Convert permalinks to lower case
        $permalink = mb_strtolower($permalink, 'UTF-8');

        return $permalink;
    }
}