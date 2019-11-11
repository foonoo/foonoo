<?php


namespace nyansapow\themes;


use nyansapow\text\TemplateEngine;

class ThemeManager
{
    private $themes;
    private $templateEngine;

    public function __construct(TemplateEngine $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    private function loadTheme($theme, $sourcePath)
    {
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
                $theme = new Theme($themePath, $this->templateEngine);
                $this->themes[$key] = $theme;
            } else {
                throw new \Exception("Could not find '$customTheme' directory for '$theme' theme");
            }
        }

        return $this->themes["$themePath$sourcePath"];
    }

    public function getTheme($theme, $sourcePath, $targetPath)
    {
        $theme = $this->loadTheme($theme, $sourcePath);
        $theme->copyAssets($targetPath);
        return $theme;
    }
}
