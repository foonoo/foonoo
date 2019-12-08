<?php


namespace nyansapow\themes;


use nyansapow\sites\AbstractSite;
use nyansapow\text\TemplateEngine;

class ThemeManager
{
    private $themes;
    private $templateEngine;

    public function __construct(TemplateEngine $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * @param AbstractSite $site
     * @return mixed
     * @throws \Exception
     */
    private function loadTheme($site)
    {
        $theme = $site->getMetaData()['theme'] ?? $site->getDefaultTheme();
        $sourcePath = $site->getSourcePath();
        $builtInTheme = __DIR__ . "/../../themes/{$theme}";
        $customTheme = "{$sourcePath}/np_themes/{$theme}";

        if (!file_exists($customTheme)) {
            $themePath = $builtInTheme;
        } else {
            $themePath = $customTheme;
        }
        $key = "$themePath$sourcePath";

        if(!isset($this->themes[$key])) {
            if (is_dir($themePath)) {
                $theme = new Theme($themePath, $this->templateEngine, $this->getLocalTemplatePaths($site));
                $this->themes[$key] = $theme;
            } else {
                throw new \Exception("Could not find '$customTheme' directory for '$theme' theme");
            }
        }

        return $this->themes[$key];
    }

    public function getTheme($site)
    {
        $theme = $this->loadTheme($site);
        $theme->copyAssets($site->getDestinationPath());
        return $theme;
    }

    /**
     * @param AbstractSite $site
     * @return array
     */
    private function getLocalTemplatePaths($site)
    {
        $hierarchy = [__DIR__ . "/../../themes/parser"];
        $path = $site->getSourcePath();

        if (is_dir("{$path}np_templates")) {
            $hierarchy[] = "{$path}np_templates";
        }

        $siteTemplates = $site->getSetting('templates');
        if (is_array($siteTemplates)) {
            foreach ($siteTemplates as $template) {
                $hierarchy []= $path . $template;
            }
        } else if ($siteTemplates) {
            $hierarchy []= $path . $siteTemplates;
        }

        return $hierarchy;
    }
}
