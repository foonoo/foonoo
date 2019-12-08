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

    public function create($source, $destination, $data): ContentInterface
    {
        return new TemplateContent($this->templateRenderer, $source, $destination, $data);
    }
}
