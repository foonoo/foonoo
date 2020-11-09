<?php

namespace foonoo\content;

use foonoo\content\ContentFactory;
use foonoo\content\Content;
use foonoo\content\MarkupContent;
use foonoo\sites\AbstractSite;
use foonoo\sites\FrontMatterReader;
use foonoo\text\TextConverter;

class MarkupContentFactory implements ContentFactory
{
    private $htmlRenderer;
    private $frontMatterReader;

    public function __construct(TextConverter $htmlRenderer, FrontMatterReader $frontMatterReader)
    {
        $this->htmlRenderer = $htmlRenderer;
        $this->frontMatterReader = $frontMatterReader;
    }

    public function create(string $source, string $destination): Content
    {
        return new MarkupContent($this->htmlRenderer, $this->frontMatterReader, $source, $destination);
    }
}
