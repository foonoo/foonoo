<?php


namespace nyansapow\sites;


use nyansapow\text\HtmlRenderer;
use nyansapow\text\TemplateEngine;

class BlogPostContent extends MarkupContent implements ThemableInterface
{
    private $templateData;
    private $metaData;
    protected $template = "post";

    /**
     * The next post
     * @var BlogPostContent
     */
    private $next;

    /**
     * The previous post
     * @var BlogPostContent
     */
    private $previous;

    /**
     * An instance of the HTML renderer
     * @var HtmlRenderer
     */
    private $htmlRenderer;
    private $templateEngine;

    public function __construct(TemplateEngine $templateEngine, HtmlRenderer $htmlRenderer, FrontMatterReader $frontMatterReader, $document, $destination)
    {
        parent::__construct($htmlRenderer, $frontMatterReader, $document, $destination);
        $this->htmlRenderer = $htmlRenderer;
        $this->templateEngine = $templateEngine;
    }

    public function getMetaData() : array
    {
        if(!$this->metaData) {
            $templateData = $this->site->getTemplateData($this->site->getDestinationPath($this->getDestination()));
            preg_match(
            "/(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})-(?<title>[A-Za-z0-9\-\_]*)\.(md)/",
                $this->getDestination(), $matches);
            $frontMatter = $this->getFrontMatter();
            $this->metaData = [
                'title' => $frontMatter['title'] ?? ucfirst(str_replace("-", " ", $this->templateData['title'])),
                'date' => isset($this->templateData['year'])
                    ? date("jS F Y", strtotime("{$this->templateData['year']}-{$this->templateData['month']}-{$this->templateData['day']}")) : "",
                'frontmatter' => $frontMatter,
                'path' => $this->getDestination(),
                'home_path' => $this->templateData['home_path'],
                'site_path' => $this->templateData['site_path']
            ];
        }
        return $this->metaData;
    }

    public function render(): string
    {
        $nextPost = $this->next ? $this->next->getMetaData() : [];
        $prevPost = $this->previous ? $this->previous->getMetaData() : [];
        return $this->templateEngine->render($this->template,
            array_merge(
                ['body' => parent::render(), 'page_type' => 'post', 'next' => $nextPost, 'prev' => $prevPost],
                $this->getMetaData()
            )
        );
    }

    public function getPreview() : string
    {
        $splitPost = $this->splitPost();
        return $this->htmlRenderer->render($splitPost['preview'], $this->site , $this);
    }

    private function splitPost()
    {
        $post = $this->getBody();
        $previewRead = false;
        $lines = explode("\n", $post);
        $preview = '';
        $body = '';
        $continuation = '';
        $moreLink = false;

        foreach ($lines as $line) {
            if (preg_match("/(?<preview>.*)(?<tag>\<\!\-\-\s*more\s*\-\-\>)(?<line>.*)/i", $line, $matches)) {
                $preview .= "{$matches['preview']}\n";
                $body .= "{$matches['preview']} {$matches['line']}\n";
                $previewRead = true;
                $moreLink = true;
                $continuation .= "{$matches['line']}\n";
                continue;
            }
            if (!$previewRead) {
                $preview .= "$line\n";
            } else {
                $continuation .= "$line\n";
            }
            $body .= "$line\n";
        }

        return ['post' => $body, 'preview' => $preview, 'more_link' => $moreLink, "continuation" => $continuation];
    }

    public function setNext(BlogPostContent $next)
    {
        $this->next = $next;
    }

    public function setPrevious(BlogPostContent $previous)
    {
        $this->previous = $previous;
    }

    public function getLayoutData()
    {
        return ['page_type' => 'post'];
    }
}
