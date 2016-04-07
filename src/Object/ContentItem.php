<?php

namespace allejo\stakx\Object;

use allejo\stakx\Core\MarkdownEngine;
use allejo\stakx\System\Filesystem;
use allejo\stakx\Exception\YamlVariableNotFound;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

class ContentItem
{
    /**
     * Set to true if the front matter has already been evaluated with variable interpolation
     *
     * @var bool
     */
    protected $frontMatterEvaluated;

    /**
     * An array containing the Yaml of the file
     *
     * @var array
     */
    protected $frontMatter;

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
     * The permalink that will be used for this individual. If this value is null, then no page will be generated for
     * this ContentItem.
     *
     * @var string|NULL
     */
    protected $permalink;

    /**
     * The original file path to the ContentItem
     *
     * @var string
     */
    protected $filePath;

    /**
     * A datetime object if the front matter contains a `date` field
     *
     * @var \DateTime
     */
    protected $itemDate;

    /**
     * The raw content of the file; yaml + markdown
     *
     * @var string
     */
    protected $raw;

    /**
     * A filesystem object
     *
     * @var Filesystem
     */
    protected $fs;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
        $this->fs = new Filesystem();

        if (!$this->fs->exists($filePath))
        {
            throw new FileNotFoundException();
        }

        $this->extension  = $this->fs->getExtension($filePath);
        $this->raw = file_get_contents($filePath);

        $frontMatter = array();
        preg_match('/---(.*?)---(.*)/s', $this->raw, $frontMatter);

        $this->frontMatter = Yaml::parse($frontMatter[1]);
        $this->bodyContent = trim($frontMatter[2]);

        if (isset($this->frontMatter['date']))
        {
            try
            {
                $this->itemDate = new \DateTime($this->frontMatter['date']);
            }
            catch (\Exception $e)
            {
                $this->itemDate = \DateTime::createFromFormat("U", $this->frontMatter['date']);
            }

            $this->frontMatter['year']  = $this->itemDate->format('Y');
            $this->frontMatter['month'] = $this->itemDate->format('m');
            $this->frontMatter['day']   = $this->itemDate->format('d');
        }

        if (isset($this->frontMatter['permalink']))
        {
            $this->permalink = $this->frontMatter['permalink'];
        }
    }

    public function __get ($name)
    {
        return $this->frontMatter[$name];
    }

    public function __isset ($name)
    {
        return array_key_exists($name, $this->frontMatter);
    }

    public function getFrontMatter ($evaluateYaml = true)
    {
        if (!$this->frontMatterEvaluated && $evaluateYaml)
        {
            $this->evaluateYaml($this->frontMatter);
            $this->frontMatterEvaluated = true;
        }

        return $this->frontMatter;
    }

    public function getPermalink ()
    {
        return $this->permalink;
    }

    public function setPermalink ($permalink, $variables = null)
    {
        if (!is_null($variables))
        {
            $this->permalink = self::evaluateYamlVar($permalink, $variables);
            $this->permalink = str_replace(' ', '-', $this->permalink);
            $this->permalink = preg_replace('/[^A-Za-z0-9\-\/]/', '', $this->permalink);
            $this->frontMatter['permalink'] = $this->permalink;
        }
        else
        {
            $this->permalink = $permalink;
            $this->frontMatter['permalink'] = $permalink;
        }
    }

    public function getTargetFile ()
    {
        $extension  = $this->fs->getExtension($this->getPermalink());
        $targetFile = $this->getPermalink();

        if ($extension === "")
        {
            $targetFile = rtrim($this->getPermalink(), '/') . '/index.html';
        }

        return ltrim($targetFile, '/');
    }

    public function getFilePath ()
    {
        return $this->filePath;
    }

    public function getContent ()
    {
        $pd = new MarkdownEngine();

        return $pd->parse($this->bodyContent);
    }

    protected function evaluateYaml ($yaml)
    {
        foreach ($yaml as $key => $value)
        {
            if (is_array($yaml[$key]))
            {
                $this->evaluateYaml($yaml[$key]);
            }
            else
            {
                $this->frontMatter[$key] = $this->evaluateYamlVar($value, $this->frontMatter);
            }
        }
    }

    protected static function evaluateYamlVar ($string, $yaml)
    {
        $variables = array();
        $varRegex  = '/(:[a-zA-Z_\-]+)/';
        $output    = $string;

        preg_match_all($varRegex, $string, $variables);

        foreach ($variables[1] as $variable)
        {
            $yamlVar = substr($variable, 1);

            if (!array_key_exists($yamlVar, $yaml))
            {
                throw new YamlVariableNotFound("Yaml variable `$variable` is not defined");
            }

            $output = str_replace($variable, $yaml[$yamlVar], $output);
        }

        return $output;
    }
}