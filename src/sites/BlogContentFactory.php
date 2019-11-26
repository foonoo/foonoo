<?php


namespace nyansapow\sites;


use nyansapow\text\HtmlRenderer;

class BlogContentFactory implements ContentFactoryInterface
{
    private $htmlRenderer;
    private $frontMatterReader;

    public function __construct(HtmlRenderer $htmlRenderer, FrontMatterReader $frontMatterReader)
    {
        $this->htmlRenderer = $htmlRenderer;
        $this->frontMatterReader = $frontMatterReader;
    }

    public function create($source, $destination, $data): ContentInterface
    {
        return new BlogContent($this->htmlRenderer, $this->frontMatterReader, $source, $destination, $data);
    }
}
