<?php


namespace foonoo\themes;


use ntentan\utils\exceptions\FileNotFoundException;
use ntentan\utils\exceptions\FileNotReadableException;
use ntentan\utils\exceptions\FilesystemException;
use ntentan\utils\Filesystem;
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

    public function getAssets()
    {
        return $this->definition['assets'] ?? [];
    }

    public function activate()
    {
        $this->templateEngine->setPathHierarchy($this->templateHierachy);
    }

    public function getDefaultLayoutTemplate()
    {
        return 'layout';
    }

    public function getPath()
    {
        return $this->themePath;
    }
}
