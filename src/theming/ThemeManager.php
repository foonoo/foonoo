<?php

namespace foonoo\theming;

use foonoo\sites\AbstractSite;
use foonoo\text\TemplateEngine;
use Symfony\Component\Yaml\Parser;
use foonoo\exceptions\SiteGenerationException;
use ntentan\utils\Text;

/**
 * Loads themes by injecting their paths into the global template hierarchy and copying all required assets to the
 * site's destination path.
 *
 * @package foonoo\theming
 */
class ThemeManager
{

    private $themes;
    private $templateEngine;
    private $yamlParser;

    public function __construct(TemplateEngine $templateEngine, Parser $yamlParser)
    {
        $this->templateEngine = $templateEngine;
        $this->yamlParser = $yamlParser;
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

        // Resolve the theme's name and options.
        if (is_array($theme)) {
            $themeName = $theme['name'];
            $themeOptions = $theme;
        } else {
            $themeName = $theme;
            $themeOptions = [];
        }

        // Determine the actual path of the theme, while distinguishing between a built in theme and an external one.
        $builtInTheme = __DIR__ . "/../../themes/{$themeName}";
        $customTheme = "{$sourcePath}/_foonoo/themes/{$themeName}";

        if (!file_exists($customTheme)) {
            $themePath = $builtInTheme;
        } else {
            $themePath = $customTheme;
        }
        $key = "$themePath$sourcePath";

        if (isset($this->themes[$key])) {
            $theme = $this->themes[$key];
            $theme->setOptions($themeOptions);
            return $theme;
        }

        if (is_dir($themePath) && file_exists("$themePath/theme.yaml")) {
            $definition = $this->yamlParser->parse(file_get_contents("$themePath/theme.yaml"));
            //$definition['path'] = $themePath;
            $definition['template_hierarchy'] = $this->getTemplateHierarchy($site, $definition, $themePath);
            //$theme = new Theme($themePath, $this->templateEngine, $definition, $themeOptions);
            $themeClassName = Text::ucamelize($definition['name']) . "Theme";                
            $expectedClassFilePath = $themePath . DIRECTORY_SEPARATOR . $themeClassName . ".php";
            $classFilePath = realpath($expectedClassFilePath);
            if($classFilePath === false) {
                throw new SiteGenerationException("Failed to load theme '$themeName'. Could not find the class file [$expectedClassFilePath]");
            }
            include_once $classFilePath;
            $themeClass = "foonoo\\themes\\{$definition['name']}\\$themeClassName";
            $theme = new $themeClass($themePath, $this->templateEngine, $definition, $themeOptions);
            $this->themes[$key] = $theme;
        } else {
            throw new SiteGenerationException("Failed to load theme '$themeName'. Could not find a theme.yaml file.");
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
     * @param AbstractSite $site
     * @param array $themeDefinition
     * @return array
     */
    private function getTemplateHierarchy(AbstractSite $site, array $themeDefinition, string $themePath): array
    {
        $hierarchy = [__DIR__ . "/../../themes/parser"];
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

        return $hierarchy;
    }

}
