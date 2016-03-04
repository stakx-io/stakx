<?php

namespace allejo\stakx\Object;

use allejo\stakx\Environment\Filesystem;
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
            $this->itemDate    = new \DateTime($this->frontMatter['date']);
            $this->frontMatter['year']  = $this->itemDate->format('Y');
            $this->frontMatter['month'] = $this->itemDate->format('m');
            $this->frontMatter['day']   = $this->itemDate->format('d');
        }
    }

    public function getFrontMatter ()
    {
        if (!$this->frontMatterEvaluated)
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

    public function getContent ()
    {
        $pd = new \Parsedown();

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
        $varRegex  = '/(:[a-zA-Z0-9_\-]+)/';
        $output    = $string;

        preg_match_all($varRegex, $string, $variables);

        foreach ($variables[1] as $variable)
        {
            $yamlVar = substr($variable, 1);

            if (array_key_exists($yamlVar, $yaml))
            {
                throw new YamlVariableNotFound("Yaml variable `$variable` is not defined");
            }

            $output = str_replace($variable, $yaml[$yamlVar], $output);
        }

        return $output;
    }
}