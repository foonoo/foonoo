<?php

namespace foonoo\themes;


use foonoo\sites\AbstractSite;
use foonoo\sites\AssetPipeline;
use foonoo\text\TemplateEngine;
use Symfony\Component\Yaml\Parser;

/**
 * Loads themes by injecting their paths into the global template hierarchy and copying all required assets to the
 * site's destination path.
 *
 * @package foonoo\themes
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
     * @param AbstractSite $site
     * @return Theme
     * @throws \Exception
     */
    private function loadTheme(AbstractSite $site) : Theme
    {
        $theme = $site->getMetaData()['theme'] ?? $site->getDefaultTheme();
        $sourcePath = $site->getSourcePath();
        $builtInTheme = __DIR__ . "/../../themes/{$theme}";
        $customTheme = "{$sourcePath}/_foonoo/themes/{$theme}";

        if (!file_exists($customTheme)) {
            $themePath = $builtInTheme;
        } else {
            $themePath = $customTheme;
        }
        $key = "$themePath$sourcePath";

        if(!isset($this->themes[$key])) {
            if (is_dir($themePath) && file_exists("$themePath/theme.yaml")) {
                $definition = $this->yamlParser->parse(file_get_contents("$themePath/theme.yaml"));
                $definition['path'] = $themePath;
                $definition['template_hierarchy'] = $this->getTemplateHierarchy($site, $definition);
                $theme = new Theme($themePath, $this->templateEngine, $definition);
                $this->themes[$key] = $theme;
            } else {
                throw new \Exception("Failed to load theme '$theme'.");
            }
        }

        return $this->themes[$key];
    }

    public function getTheme(AbstractSite $site) : Theme
    {
        $theme = $this->loadTheme($site);
        $site->getAssetPipeline()->merge($theme->getAssets(), $theme->getPath());
        $theme->activate();
        return $theme;
    }

    /**
     * @param AbstractSite $site
     * @param array $themeDefinition
     * @return array
     */
    private function getTemplateHierarchy(AbstractSite $site, array $themeDefinition) : array
    {
        $hierarchy = [__DIR__ . "/../../themes/parser"];
        $path = $site->getSourcePath();

        if (is_dir("{$path}_foonoo/templates")) {
            $hierarchy[] = "{$path}_foonoo/templates";
        }

        $siteTemplates = $site->getSetting('templates');
        if (is_array($siteTemplates)) {
            foreach ($siteTemplates as $template) {
                $hierarchy []= $path . $template;
            }
        } else if ($siteTemplates) {
            $hierarchy []= $path . $siteTemplates;
        }

        if(isset($themeDefinition['template_hierarchy'])) {
            $hierarchy = array_merge($hierarchy, $themeDefinition['template_hierarchy']);
        } else {
            $hierarchy[] = "{$themeDefinition['path']}/templates";
        }

        return $hierarchy;
    }
}
