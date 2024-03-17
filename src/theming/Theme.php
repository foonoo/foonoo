<?php
namespace foonoo\theming;

use foonoo\text\TemplateEngine;
use foonoo\asset_pipeline\AssetPipeline;

/**
 * The base class for foonoo themes. 
 * In cases where themes are simple YAML files, this class simply wraps the YAML. Themes that are represented as 
 * classes, must however extend this class.
 */
class Theme
{
    /**
     * Path to the theme
     * @var string
     */
    private string $themePath;
    
    /**
     * An instance of the template engine.
     * @var TemplateEngine
     */
    private TemplateEngine $templateEngine;
    
    /**
     * The template path hierarchy for this theme.
     * @var array
     */
    private array $templateHierachy;
    
    /**
     * Data read out from the theme.yml definition file.
     * @var array
     */
    private array $definition;
    
    /**
     * Options passed to this theme.
     * @var array
     */
    private array $themeOptions;

    public final function __construct(string $themePath, TemplateEngine $templateEngine, array $themeDefinition, array $themeOptions)
    {
        $this->themePath = $themePath;
        $this->templateEngine = $templateEngine;
        $this->templateHierachy = $themeDefinition['template_hierarchy'];
        $this->definition = $themeDefinition;
        $this->themeOptions = $themeOptions;
    }
    
    public function setOptions(array $options) : void
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
    
    protected function activate(AssetPipeline $assetPipeline) : void
    {
        // Do nothing
        // can be extended by sub-classes that need to modify the assets pipeline when themes are loaded
    }
    
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
