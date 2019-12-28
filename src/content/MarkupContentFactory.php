<?php

namespace nyansapow\content;

use nyansapow\content\ContentFactoryInterface;
use nyansapow\content\ContentInterface;
use nyansapow\content\MarkupContent;
use nyansapow\sites\AbstractSite;
use nyansapow\sites\FrontMatterReader;
use nyansapow\text\HtmlRenderer;

class MarkupContentFactory implements ContentFactoryInterface
{
    private $htmlRenderer;
    private $frontMatterReader;

    public function __construct(HtmlRenderer $htmlRenderer, FrontMatterReader $frontMatterReader)
    {
        $this->htmlRenderer = $htmlRenderer;
        $this->frontMatterReader = $frontMatterReader;
    }

    public function create(AbstractSite $site, string $source, string $destination): ContentInterface
    {
        $content = new MarkupContent($this->htmlRenderer, $this->frontMatterReader, $source, $destination);
        $content->setSite($site);
        return $content;
    }
}
