<?php


namespace nyansapow\events;


use nyansapow\sites\AutomaticContentFactory;
use nyansapow\sites\SiteTypeRegistry;
use nyansapow\text\TagParser;

class PluginsInitialized
{
    private $tagParser;
    private $automaticContentFactory;
    private $siteTypeRegistry;

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
