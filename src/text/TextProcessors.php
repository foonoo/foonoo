<?php

namespace nyansapow\text;

use \Symfony\Component\Yaml\Parser as YamlParser;

class TextProcessors
{
    private $textParser;
    private $yamlParser;
    private $textRenderer;

    public function __construct(YamlParser $yamlParser, Parser $textParser, HtmlRenderer $textRenderer)
    {
        $this->textParser = $textParser;
        $this->yamlParser = $yamlParser;
        $this->textRenderer = $textRenderer;
    }

    public function parseYaml(string $yamlText)
    {
        return $this->yamlParser->parse($yamlText);
    }

    public function setPathToBase(string $path)
    {
        $this->textParser->setPathToBase($path);
    }

    public function renderHtml(string $markdown, string $format, $options = []) : string
    {
        return $this->textRenderer->render($markdown, $format, $options);
    }

    public function isFileRenderable(string $path) : bool
    {
        return $this->textRenderer->isFileRenderable($path);
    }
}
