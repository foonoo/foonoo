<?php


namespace nyansapow\events;


use nyansapow\text\TemplateEngine;
use nyansapow\themes\Theme;

class ThemeLoaded
{
    private $theme;
    private $templateEngine;

    public function __construct(Theme $theme, TemplateEngine $templateEngine)
    {
        $this->theme = $theme;
        $this->templateEngine = $templateEngine;
    }

    public function getTheme() : Theme
    {
        return $this->theme;
    }

    public function getTemplateEngine() : TemplateEngine
    {
        return $this->templateEngine;
    }
}
