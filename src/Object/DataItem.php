<?php

namespace allejo\stakx\Object;

use allejo\stakx\Environment\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Yaml\Yaml;

class DataItem
{
    protected $converters;
    protected $content;
    protected $data;
    protected $name;
    protected $ext;
    protected $fs;

    public function __construct ($filePath)
    {
        $this->fs      = new Filesystem();
        $this->ext     = strtolower($this->fs->getExtension($filePath));
        $this->name    = $this->fs->getFileName($filePath);
        $this->content = $this->readFile($filePath);
        $functionName  = 'from' . ucfirst($this->ext);

        if (method_exists(get_called_class(), $functionName))
        {
            $this->data = $this->$functionName($this->content);
        }
        else
        {
            throw new IOException("There is no function to handle '$this->ext' file format.");
        }
    }

    public function getName ()
    {
        return $this->name;
    }

    private function fromCsv ($content)
    {
        $rows    = array_map("str_getcsv", explode("\n", trim($content)));
        $columns = array_shift($rows);
        $csv     = array();

        foreach ($rows as $row)
        {
            $csv[] = array_combine($columns, $row);
        }

        return $csv;
    }

    private function fromJson ($content)
    {
        return json_decode($content, true);
    }

    private function fromXml ($content)
    {
        return json_decode(json_encode(simplexml_load_string($content)), true);
    }

    private function fromYaml ($content)
    {
        return Yaml::parse($content);
    }

    private function readFile ($filePath)
    {
        if (!$this->fs->exists($filePath))
        {
            throw new IOException("No file could be found at: $filePath");
        }

        return file_get_contents($filePath);
    }
}