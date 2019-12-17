<?php


namespace nyansapow\sites;


use ntentan\honam\TemplateRenderer;

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
