<?php


namespace nyansapow\content;


use ntentan\honam\TemplateRenderer;
use nyansapow\content\ContentFactory;
use nyansapow\content\Content;
use nyansapow\content\TemplateContent;
use nyansapow\sites\AbstractSite;

class TemplateContentFactory implements ContentFactory
{
    private $templateRenderer;

    public function __construct(TemplateRenderer $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
    }

    public function create(string $source, string $destination): Content
    {
        return new TemplateContent($this->templateRenderer,$source, $destination);
    }
}
