<?php

namespace allejo\stakx\Core;

use allejo\stakx\Utilities\StakxFilesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

class ContentItem
{
    private $fs;

    protected $frontMatter;
    protected $fileContent;
    protected $rawContent;
    protected $extension;

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
        preg_match('/---(.*)---(.*)/s', $this->rawContent, $frontMatter);

        $this->frontMatter = Yaml::parse($frontMatter[1]);
        $this->fileContent = trim($frontMatter[2]);
    }

    public function getContent ()
    {
        $pd = new \Parsedown();

        return $pd->parse($this->fileContent);
    }

    public function getFrontMatter ()
    {
        return $this->frontMatter;
    }
}