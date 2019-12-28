<?php


namespace nyansapow\content;


use ntentan\honam\TemplateRenderer;
use nyansapow\content\ContentFactoryInterface;
use nyansapow\content\ContentInterface;
use nyansapow\content\TemplateContent;
use nyansapow\sites\AbstractSite;

class TemplateContentFactory implements ContentFactoryInterface
{
    private $templateRenderer;

    public function __construct(TemplateRenderer $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
    }

    public function create(AbstractSite $site, string $source, string $destination): ContentInterface
    {
        return new TemplateContent($this->templateRenderer, $site, $source, $destination);
    }
}
