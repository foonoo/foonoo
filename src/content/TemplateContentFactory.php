<?php


namespace foonoo\content;


use ntentan\honam\TemplateRenderer;
use foonoo\events\EventDispatcher;
use foonoo\events\SiteWriteStarted;
use foonoo\text\TagParser;

class TemplateContentFactory implements ContentFactory
{
    private $templateRenderer;
    private $site;
    private $parser;

    public function __construct(TemplateRenderer $templateRenderer, EventDispatcher $eventDispatcher, TagParser $parser)
    {
        $this->templateRenderer = $templateRenderer;
        $this->parser = $parser;
        $eventDispatcher->addListener(
            SiteWriteStarted::class,
            function(SiteWriteStarted $event) {
                $this->site = $event->getSite();
            });
    }

    public function create(string $source, string $destination): Content
    {
        $content = new TemplateContent($this->templateRenderer, $this->parser, $source, $destination);
        if($this->site) {
            $content->setData($this->site->getTemplateData($this->site->getDestinationPath($destination)));
        }
        return $content;
    }
}
