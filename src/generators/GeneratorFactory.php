<?php

namespace nyansapow\generators;


use nyansapow\text\TemplateEngine;
use nyansapow\text\TextProcessors;

class GeneratorFactory
{
    private $textProcessors;
    private $templateEngine;

    public function __construct(TextProcessors $textProcessors, TemplateEngine $templateEngine)
    {
        $this->templateEngine = $templateEngine;
        $this->textProcessors = $textProcessors;
    }

    public function create($settings = [])
    {
        $class = "\\nyansapow\\generators\\" . ucfirst($settings['type']) . "Generator";
        return new $class($this->textProcessors, $this->templateEngine, $settings);
    }
}
