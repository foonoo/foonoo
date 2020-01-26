<?php


namespace nyansapow\events;


use nyansapow\content\AutomaticContentFactory;
use nyansapow\sites\SiteTypeRegistry;
use nyansapow\text\TagParser;
use nyansapow\text\TemplateEngine;

class PluginsInitialized //extends Event
{
    private $tagParser;
    private $automaticContentFactory;
    private $siteTypeRegistry;
    private $templateEngine;

    /**
     * PluginsInitialized constructor.
     *
     * @param TagParser $tagParser So plugins can create custom tags.
     * @param AutomaticContentFactory $automaticContentFactory So plugins can register new content factories.
     * @param SiteTypeRegistry $siteTypeRegistry So plugins can register new site types.
     * @param TemplateEngine $templateEngine So plugins can register new template engines.
     */
    public function __construct(TagParser $tagParser, AutomaticContentFactory $automaticContentFactory, SiteTypeRegistry $siteTypeRegistry, TemplateEngine $templateEngine)
    {
        $this->tagParser = $tagParser;
        $this->automaticContentFactory = $automaticContentFactory;
        $this->siteTypeRegistry = $siteTypeRegistry;
        $this->templateEngine = $templateEngine;
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

    public function getTemplateEngine() : TemplateEngine
    {
        return $this->templateEngine;
    }
}
