<?php


namespace foonoo\theming;


use foonoo\text\TemplateEngine;
use foonoo\asset_pipeline\AssetPipeline;

/**
 * A class wrapped around a theme.
 * 
 */
abstract class Theme
{
    /**
     * Path to the theme
     * @var string
     */
    private $themePath;
    
    /**
     * An instance of the template engine.
     * @var TemplateEngine
     */
    private $templateEngine;
    
    /**
     * The template path hierarchy for this theme.
     * @var array
     */
    private $templateHierachy;
    
    /**
     * Data read out from the theme.yml definition file.
     * @var array
     */
    private $definition;
    
    /**
     * Options passed to this theme.
     * @var array
     */
    private $themeOptions;

    public final function __construct(string $themePath, TemplateEngine $templateEngine, array $themeDefinition, array $themeOptions)
    {
        $this->themePath = $themePath;
        $this->templateEngine = $templateEngine;
        $this->templateHierachy = $themeDefinition['template_hierarchy'];
        $this->definition = $themeDefinition;
        $this->themeOptions = $themeOptions;
    }
    
    public function setOptions($options)
    {
        $this->themeOptions = $options;
    }

    /**
     * Return the list of assets this theme used.
     * @return array
     */
    public function getAssets(): array
    {
        return $this->definition['assets'] ?? [];
    }

    /**
     * The name of the default template for the theme.
     * @return string
     */
    public function getDefaultLayoutTemplate(): string
    {
        return $this->definition['default-layout'] ?? 'layout';
    }

    /**
     * Return the path of the theme.
     * @return string
     */
    public function getPath(): string
    {
        return $this->themePath;
    }
    
    public abstract function activate(AssetPipeline $assetPipeline);
    
    public final function initialize(AssetPipeline $assetPipeline)
    {
        $this->activate($assetPipeline);
        $this->templateEngine->setPathHierarchy($this->templateHierachy);
    }

    protected function getOptions() : array
    {
        return $this->themeOptions;
    }

    protected function getDefinition() : array
    {
        return $this->definition;
    }
}
