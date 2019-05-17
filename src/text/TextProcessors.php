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

    public function parseYaml($yamlText)
    {
        return $this->yamlParser->parse($yamlText);
    }

    public function setPathToBase($path)
    {
        $this->textParser->setPathToBase($path);
    }

    public function renderHtml($markdown, $format, $options = [])
    {
        return $this->textRenderer->render($markdown, $format, $options);
    }
}
