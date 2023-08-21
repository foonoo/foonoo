<?php


namespace foonoo\content;


use foonoo\sites\FrontMatterReader;
use foonoo\text\TextConverter;
use foonoo\text\TemplateEngine;

class BlogContentFactory
{
    private $htmlRenderer;
    private $frontMatterReader;
    private $templateEngine;

    public function __construct(TemplateEngine $templateEngine, TextConverter $htmlRenderer, FrontMatterReader $frontMatterReader)
    {
        $this->htmlRenderer = $htmlRenderer;
        $this->frontMatterReader = $frontMatterReader;
        $this->templateEngine = $templateEngine;
    }

    public function createPost(string $source, string $destination): BlogPostContent
    {
        return new BlogPostContent($this->templateEngine, $this->htmlRenderer, $this->frontMatterReader, $source, $destination);
    }

    public function createPage($source, $destination) : BlogPageContent
    {
        return new BlogPageContent($this->templateEngine, $this->htmlRenderer, $this->frontMatterReader, $source, $destination);
    }

    public function createListing($posts, $destination, $title) : BlogListingContent
    {
        $listing = new BlogListingContent($this->templateEngine, $posts, $destination);
        $listing->setTitle($title);
        return $listing;
    }
}
