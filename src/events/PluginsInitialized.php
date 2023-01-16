<?php

namespace foonoo\events;

use foonoo\theming\ThemeManager;
use foonoo\content\AutomaticContentFactory;
use foonoo\sites\SiteTypeRegistry;
use foonoo\text\TagParser;
use foonoo\text\TemplateEngine;

class PluginsInitialized //extends Event
{
    private $tagParser;
    private $automaticContentFactory;
    private $siteTypeRegistry;

    /**
     * PluginsInitialized constructor.
     *
     * @param TagParser $tagParser So plugins can create custom tags.
     * @param AutomaticContentFactory $automaticContentFactory So plugins can register new content factories.
     * @param SiteTypeRegistry $siteTypeRegistry So plugins can register new site types.
     * @param TemplateEngine $templateEngine So plugins can register new template engines.
     */
    public function __construct(TagParser $tagParser, AutomaticContentFactory $automaticContentFactory, SiteTypeRegistry $siteTypeRegistry)
    {
        $this->tagParser = $tagParser;
        $this->automaticContentFactory = $automaticContentFactory;
        $this->siteTypeRegistry = $siteTypeRegistry;
    }

    public function getTagParser() : TagParser
    {
        return $this->tagParser;
    }

    public function getContentFactory() : AutomaticContentFactory
    {
        return $this->automaticContentFactory;
    }

    public function getSiteTypeRegistry() : SiteTypeRegistry
    {
        return $this->siteTypeRegistry;
    }
}
