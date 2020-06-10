<?php


namespace foonoo\content;


use ntentan\honam\TemplateRenderer;
use foonoo\content\ContentFactory;
use foonoo\content\Content;
use foonoo\content\TemplateContent;
use foonoo\events\EventDispatcher;
use foonoo\events\SiteWriteStarted;
use foonoo\sites\AbstractSite;

class TemplateContentFactory implements ContentFactory
{
    private $templateRenderer;
    private $site;

    public function __construct(TemplateRenderer $templateRenderer, EventDispatcher $eventDispatcher)
    {
        $this->templateRenderer = $templateRenderer;
        $eventDispatcher->addListener(
            SiteWriteStarted::class,
            function(SiteWriteStarted $event) {
                $this->site = $event->getSite();
            });
    }

    public function create(string $source, string $destination): Content
    {
        $content = new TemplateContent($this->templateRenderer,$source, $destination);
        if($this->site) {
            $content->setData($this->site->getTemplateData($this->site->getDestinationPath($destination)));
        }
        return $content;
    }
}
