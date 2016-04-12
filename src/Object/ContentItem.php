<?php

namespace allejo\stakx\Object;

use allejo\stakx\Core\MarkdownEngine;
use allejo\stakx\System\Filesystem;
use allejo\stakx\Exception\YamlVariableNotFound;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
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
     * A filesystem object
     *
     * @var Filesystem
     */
    protected $fs;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
        $this->fs       = new Filesystem();

        if (!$this->fs->exists($filePath))
        {
            throw new FileNotFoundException();
        }

        $this->extension = $this->fs->getExtension($filePath);
        $rawFileContents = file_get_contents($filePath);

        $frontMatter = array();
        preg_match('/---(.*?)---(.*)/s', $rawFileContents, $frontMatter);

        if (count($frontMatter) != 3)
        {
            throw new IOException('This file is not a valid ContentItem');
        }

        if (empty(trim($frontMatter[2])))
        {
            throw new IOException('A ContentItem must have a body to render');
        }

        $this->frontMatter = Yaml::parse($frontMatter[1]);
        $this->bodyContent = trim($frontMatter[2]);

        $this->handleDefaults();
    }

    public function __get ($name)
    {
        return (array_key_exists($name, $this->frontMatter) ? $this->frontMatter[$name] : null);
    }

    public function getFrontMatter ($evaluateYaml = true)
    {
        if ($this->frontMatter === null)
        {
            $this->frontMatter = array();
        }
        else if (!$this->frontMatterEvaluated && $evaluateYaml && !empty($evaluateYaml))
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
            $this->permalink = preg_replace('/[^A-Za-z0-9\-\/\.\_]/', '', $this->permalink);
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

        if (empty($extension) && !is_null($targetFile))
        {
            $targetFile = rtrim($this->getPermalink(), '/') . '/index.html';
        }
        else if (is_null($targetFile))
        {
            $targetFile = $this->fs->getBaseName($this->filePath);
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

    protected function evaluateYaml (&$yaml)
    {
        foreach ($yaml as $key => $value)
        {
            if (is_array($yaml[$key]))
            {
                $this->evaluateYaml($yaml[$key]);
            }
            else
            {
                $yaml[$key] = $this->evaluateYamlVar($value, $this->frontMatter);
            }
        }
    }

    protected static function evaluateYamlVar ($string, $yaml)
    {
        $variables = array();
        $varRegex  = '/(%[a-zA-Z_\-]+)/';
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

    private function handleDefaults ()
    {
        if (isset($this->frontMatter['date']))
        {
            try
            {
                $itemDate = new \DateTime($this->frontMatter['date']);

                $this->frontMatter['year']  = $itemDate->format('Y');
                $this->frontMatter['month'] = $itemDate->format('m');
                $this->frontMatter['day']   = $itemDate->format('d');
            }
            catch (\Exception $e) { }
        }

        if (isset($this->frontMatter['permalink']))
        {
            $this->permalink = $this->frontMatter['permalink'];
        }
    }
}