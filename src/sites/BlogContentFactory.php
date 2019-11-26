<?php


namespace nyansapow\sites;


use nyansapow\text\HtmlRenderer;
use nyansapow\text\TemplateEngine;

class BlogContentFactory implements ContentFactoryInterface
{
    private $htmlRenderer;
    private $frontMatterReader;
    private $templateEngine;

    public function __construct(TemplateEngine $templateEngine, HtmlRenderer $htmlRenderer, FrontMatterReader $frontMatterReader)
    {
        $this->htmlRenderer = $htmlRenderer;
        $this->frontMatterReader = $frontMatterReader;
        $this->templateEngine = $templateEngine;
    }

    public function create($source, $destination, $data): ContentInterface
    {
        return new BlogContent($this->templateEngine, $this->htmlRenderer, $this->frontMatterReader, $source, $destination, $data);
    }
}
