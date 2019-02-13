<?php

namespace nyansapow\processors;


use clearice\io\Io;
use nyansapow\Nyansapow;
use \Symfony\Component\Yaml\Parser as YamlParser;

class ProcessorFactory
{
    private $io;
    private $yamlParser;

    public function __construct(Io $io, YamlParser $yamlParser)
    {
        $this->io = $io;
        $this->yamlParser = $yamlParser;
    }

    public function create(Nyansapow $nyansapow, $settings = [], $directory = null)
    {
        $class = "\\nyansapow\\processors\\" . ucfirst($settings['type']);
        return new $class($nyansapow, $this->io, $this->yamlParser, $settings, $directory);
    }
}
