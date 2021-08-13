<?php


namespace foonoo\themes;


use foonoo\text\TemplateEngine;

class Theme
{
    private $themePath;
    private $templateEngine;
    private $templateHierachy;
    private $definition;

    public function __construct(string $themePath, TemplateEngine $templateEngine, array $themeDefinition)
    {
        $this->themePath = $themePath;
        $this->templateEngine = $templateEngine;
        $this->templateHierachy = $themeDefinition['template_hierarchy'];
        $this->definition = $themeDefinition;
    }

    public function getAssets(): array
    {
        return $this->definition['assets'] ?? [];
    }

    public function activate()
    {
        $this->templateEngine->setPathHierarchy($this->templateHierachy);
    }

    public function getDefaultLayoutTemplate(): string
    {
        return 'layout';
    }

    public function getPath(): string
    {
        return $this->themePath;
    }
}
