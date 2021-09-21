<?php


namespace foonoo\themes;


use foonoo\text\TemplateEngine;

/**
 * A class wrapped around a theme.
 * 
 */
class Theme
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

    public function __construct(string $themePath, TemplateEngine $templateEngine, array $themeDefinition, array $themeOptions)
    {
        $this->themePath = $themePath;
        $this->templateEngine = $templateEngine;
        $this->templateHierachy = $themeDefinition['template_hierarchy'];
        $this->definition = $themeDefinition;
        $this->themeOptions = $themeOptions;
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
     * A method to activate the system.
     */
    public function activate()
    {
        $path = realpath($this->themePath . DIRECTORY_SEPARATOR . "build.php");
        if($path !== false) {
            include $path;
        }
        $this->templateEngine->setPathHierarchy($this->templateHierachy);
    }

    /**
     * The name of the default template for the theme.
     * @return string
     */
    public function getDefaultLayoutTemplate(): string
    {
        return 'layout';
    }

    /**
     * Return the path of the theme.
     * @return string
     */
    public function getPath(): string
    {
        return $this->themePath;
    }
}
