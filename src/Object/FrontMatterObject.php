<?php

namespace allejo\stakx\Object;

use allejo\stakx\Environment\Filesystem;
use allejo\stakx\Exception\YamlVariableNotFound;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

abstract class FrontMatterObject
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
    protected $fileContent;

    /**
     * The raw content of the file; yaml + markdown
     *
     * @var string
     */
    protected $rawContent;

    /**
     * The extension of the file
     *
     * @var string
     */
    protected $extension;

    /**
     * A filesystem object
     *
     * @var Filesystem
     */
    protected $fs;

    protected function __construct($filePath)
    {
        $this->fs = new Filesystem();

        if (!$this->fs->exists($filePath))
        {
            throw new FileNotFoundException();
        }

        $this->extension  = $this->fs->getExtension($filePath);
        $this->rawContent = file_get_contents($filePath);

        $frontMatter = array();
        preg_match('/---(.*?)---(.*)/s', $this->rawContent, $frontMatter);

        $this->frontMatter = Yaml::parse($frontMatter[1]);
        $this->fileContent = trim($frontMatter[2]);
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