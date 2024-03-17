<?php

namespace foonoo\theming;

use foonoo\sites\AbstractSite;
use foonoo\text\TemplateEngine;
use Symfony\Component\Yaml\Parser;
use foonoo\exceptions\SiteGenerationException;
use ntentan\utils\Text;
use Phar;

/**
 * Loads themes by injecting their paths into the global template hierarchy and 
 * copying all required assets to the site's destination path.
 *
 * @package foonoo\theming
 */
class ThemeManager
{

    private $themes;
    private $templateEngine;
    private $yamlParser;
    private $themePathHierarchy = [];

    public function __construct(TemplateEngine $templateEngine, Parser $yamlParser)
    {
        $this->templateEngine = $templateEngine;
        $this->yamlParser = $yamlParser;
    }

    public function reset(): void
    {
        $this->themePathHierarchy = [realpath(__DIR__ . "/../../themes/")];
        $pharDirectory = Phar::running();
        if ($pharDirectory != "") {
            $this->themePathHierarchy[]= $pharDirectory . "/themes/";
        }
    }

    public function prependToThemePath(string $path): void
    {
        array_unshift($this->themePathHierarchy, $path);
    }

    public function appendToThemePath(string $path): void
    {
        $this->themePathHierarchy[] = $path;
    }

    /**
     * Load an instance of the theme class for a given site.
     * 
     * @param AbstractSite $site
     * @return Theme
     * @throws \Exception
     */
    private function loadTheme(AbstractSite $site): Theme
    {
        $theme = $site->getMetaData()['theme'] ?? $site->getDefaultTheme();
        $sourcePath = $site->getSourcePath();
        $themePathHierarchy = array_merge([realpath("{$sourcePath}/_foonoo/themes")], $this->themePathHierarchy);

        // Resolve the theme's name and options.
        if (is_array($theme)) {
            $themeName = $theme['name'];
            $themeOptions = $theme;
        } else {
            $themeName = $theme;
            $themeOptions = [];
        }

        // Determine the actual path of the theme, while distinguishing between a built in theme and an external one.
        foreach($themePathHierarchy as $pathToTheme) {
            $themePath = "{$pathToTheme}/{$themeName}";
            if(file_exists($themePath)) {
                break;
            }
        }

        $key = "$themePath$sourcePath";

        if (isset($this->themes[$key])) {
            $theme = $this->themes[$key];
            $theme->setOptions($themeOptions);
            return $theme;
        }

        if (is_dir($themePath) && file_exists("$themePath/theme.yaml")) {
            $definition = $this->yamlParser->parse(file_get_contents("$themePath/theme.yaml"));
            $definition['template_hierarchy'] = $this->getTemplateHierarchy($site, $definition, $themePath);
            $themeClassName = Text::ucamelize($definition['name']) . "Theme";                
            $classFilePath = $themePath . DIRECTORY_SEPARATOR . $themeClassName . ".php";
            
            // Load a theme class file if one exists or use the default instead.
            if(file_exists($classFilePath)) {
                include_once $classFilePath;
                $themeClass = "foonoo\\themes\\{$definition['name']}\\$themeClassName";                
            } else {
                $themeClass = Theme::class;
            }
            
            $theme = new $themeClass($themePath, $this->templateEngine, $definition, $themeOptions);
            $this->themes[$key] = $theme;
        } else {
            throw new SiteGenerationException("Failed to load theme '$themeName'. Could not find a valid theme.yaml file in any of the directories in the theme hierarchy: {implode(',', $this->themePathHierarchy)}");
        }

        return $this->themes[$key];
    }

    public function getTheme(AbstractSite $site): Theme
    {
        $theme = $this->loadTheme($site);
        $assetPipeline = $site->getAssetPipeline();
        $assetPipeline->merge($theme->getAssets(), $theme->getPath() . DIRECTORY_SEPARATOR . "assets");
        $theme->initialize($assetPipeline);
        return $theme;
    }

    /**
     * Generate a template hierarchy for the current site based on the current theme.
     * 
     * @param AbstractSite $site
     * @param array $themeDefinition
     * @return array
     */
    private function getTemplateHierarchy(AbstractSite $site, array $themeDefinition, string $themePath) : array
    {
        $hierarchy = []; 
        $path = $site->getSourcePath();

        if (is_dir("{$path}_foonoo/templates")) {
            $hierarchy[] = "{$path}_foonoo/templates";
        }

        $siteTemplates = $site->getSetting('templates');
        if (is_array($siteTemplates)) {
            foreach ($siteTemplates as $template) {
                $hierarchy [] = $path . $template;
            }
        } else if ($siteTemplates) {
            $hierarchy [] = $path . $siteTemplates;
        }

        if (isset($themeDefinition['template_hierarchy'])) {
            $hierarchy = array_merge($hierarchy, $themeDefinition['template_hierarchy']);
        } else {
            $hierarchy[] = "$themePath/templates";
        }

        $hierarchy[] = __DIR__ . "/../../themes/parser";
        return $hierarchy;
    }
}
