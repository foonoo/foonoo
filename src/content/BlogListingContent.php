<?php


namespace nyansapow\content;


use nyansapow\content\BlogPostContent;
use nyansapow\content\Content;
use nyansapow\content\ThemableInterface;
use nyansapow\text\TemplateEngine;

class BlogListingContent extends Content implements ThemableInterface, ContentGroupInterface
{
    private $posts;
    private $templateEngine;
    private $data;
    private $title;
    private $template = 'listing';

    public function __construct(TemplateEngine $templateRenderer, array $posts, string $destination, array $data)
    {
        $this->posts = $posts;
        $this->templateEngine = $templateRenderer;
        $this->destination = $destination;
        $this->data = $data;
    }

    public function setTitle(string $title) :void
    {
        $this->title = $title;
    }

    public function setTemplate($template) : void
    {
        $this->template = $template;
    }

    public function getMetaData(): array
    {
        return [
            'posts' => $this->posts,
            'title' => $this->title
        ];
    }

    public function render(): string
    {
        $posts = array_map(function (BlogPostContent $post) {
            $templateVars = $post->getMetaData();
            $templateVars['preview'] = $post->getPreview();
            $templateVars['previews_only'] = true;
            return $templateVars;
        }, $this->posts);

        $templateVars = array_merge($this->data, ['posts' => $posts]);
        return $this->templateEngine->render($this->template, $templateVars);
    }

    public function getLayoutData()
    {
        return ['page_type' => 'listing'];
    }

    public function getContents()
    {
        return $this->posts;
    }
}
