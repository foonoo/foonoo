<?php


namespace foonoo\events;


use foonoo\text\TemplateEngine;
use foonoo\themes\Theme;

class ThemeLoaded extends Event
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
