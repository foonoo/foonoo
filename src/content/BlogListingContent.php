<?php


namespace foonoo\content;


use foonoo\text\TemplateEngine;

class BlogListingContent extends Content implements ThemableInterface, ContentGroup
{
    private $posts;
    private $templateEngine;
    private $data;
    private $title;
    private $template = 'listing';
    private $id;

    public function __construct(TemplateEngine $templateRenderer, array $posts, string $destination, array $data)
    {
        $this->posts = $posts;
        $this->templateEngine = $templateRenderer;
        $this->destination = $destination;
        $this->data = $data;
        $this->id = uniqid("bl_", true);
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
            'frontmatter' => ['title' => $this->title],
            'path' => $this->destination
        ];
    }

    public function render(): string
    {
        $posts = array_map(function (BlogPostContent $post) {
            $templateVars = $post->getMetaData();
            $templateVars['preview'] = $post->getPreview();
            $templateVars['previews_only'] = true;
            $templateVars['site_path'] = $this->data['site_path'];
            $templateVars['home_path'] = $this->data['home_path'];
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

    public function getID(): string
    {
        return $this->id;
    }

}
