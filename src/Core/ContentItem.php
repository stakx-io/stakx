<?php

namespace allejo\stakx\Core;

use allejo\stakx\Exception\YamlVariableNotFound;
use allejo\stakx\Utilities\StakxFilesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

class ContentItem
{
    private $fs;

    protected $frontMatterEvaluated;
    protected $frontMatter;
    protected $fileContent;
    protected $rawContent;
    protected $extension;
    protected $permalink;
    protected $template;
    protected $itemDate;

    public function __construct ($filePath)
    {
        $this->fs = new StakxFilesystem();

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

        if (!isset($this->frontMatter['date']))
        {
            $this->itemDate    = new \DateTime($this->frontMatter['date']);
            $this->frontMatter['year']  = $this->itemDate->format('Y');
            $this->frontMatter['month'] = $this->itemDate->format('m');
            $this->frontMatter['day']   = $this->itemDate->format('d');
        }
    }

    public function getContent ()
    {
        $pd = new \Parsedown();

        return $pd->parse($this->fileContent);
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

    public function getTemplate ()
    {
        return $this->template;
    }

    public function _setPermalink ($permalink)
    {
        $this->permalink = $permalink;
    }

    public function _setTemplate ($template)
    {
        $this->template = $template;
    }

    private function evaluateYaml ($yaml)
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

    private static function evaluateYamlVar ($string, $yaml)
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