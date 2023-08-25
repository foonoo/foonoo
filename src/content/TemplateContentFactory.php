<?php
namespace foonoo\content;


use ntentan\honam\TemplateRenderer;
use foonoo\events\EventDispatcher;
use foonoo\events\SiteWriteStarted;
use foonoo\text\TagParser;
use foonoo\sites\FrontMatterReader;
use foonoo\sites\AbstractSite;

class TemplateContentFactory implements ContentFactory
{
    private $templateRenderer;
    private AbstractSite $site;
    private $parser;
    /**
     * 
     * @var FrontMatterReader
     */
    private $frontMatterReader;

    public function __construct(TemplateRenderer $templateRenderer, EventDispatcher $eventDispatcher, TagParser $parser, FrontMatterReader $frontMatterReader)
    {
        $this->templateRenderer = $templateRenderer;
        $this->parser = $parser;
        $eventDispatcher->addListener(
            SiteWriteStarted::class,
            function(SiteWriteStarted $event) {
                $this->site = $event->getSite();
            });
        $this->frontMatterReader = $frontMatterReader;
    }

    public function create(string $source, string $destination): Content
    {
        $content = new TemplateContent($this->templateRenderer, $this->parser, $this->frontMatterReader, $source, $destination);
        if($this->site) {
            $content->setData($this->site->getTemplateData($content));
        }
        return $content;
    }
}

