<?php


namespace nyansapow\themes;


use nyansapow\sites\AbstractSite;
use nyansapow\text\TemplateEngine;
use nyansapow\text\TemplateEngineFactory;

class ThemeManager
{
    private $themes;
    private $templateEngineFactory;

    public function __construct(TemplateEngineFactory $templateEngineFactory)
    {
        $this->templateEngineFactory = $templateEngineFactory;
    }

    /**
     * @param AbstractSite $site
     * @return mixed
     * @throws \Exception
     */
    private function loadTheme($site)
    {
        $theme = $site->getDefaultTheme();
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
                $theme = new Theme($themePath, $this->templateEngineFactory->create($site));
                $this->themes[$key] = $theme;
            } else {
                throw new \Exception("Could not find '$customTheme' directory for '$theme' theme");
            }
        }

        return $this->themes[$key];
    }

    public function getTheme($site)
    {
//        $theme = $site->getDefaultTheme();
//        $sourcePath = $site->getSourcePath();
//        $targetPath = $site->getDestinationPath();
        $theme = $this->loadTheme($site);
        $theme->copyAssets($site->getDestinationPath());
        return $theme;
    }
}
