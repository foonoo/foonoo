<?php
namespace foonoo\events;

use foonoo\sites\AbstractSite;
use foonoo\theming\ThemeManager;
use foonoo\text\TemplateEngine;

class SiteWriteStarted
{
    private $site;
    private $themeManager;
    private $templateEngine;

    public function __construct(AbstractSite $site, TemplateEngine $templateEngine, ThemeManager $themeManager)
    {
        $this->site = $site;
        $this->themeManager = $themeManager;
        $this->templateEngine = $templateEngine;
    }

    public function getSite() : AbstractSite
    {
        return $this->site;
    }

    public function getTemplateEngine() : TemplateEngine
    {
        return $this->templateEngine;
    }

    public function getThemeManager(): ThemeManager
    {
        return $this->themeManager;
    }
}
