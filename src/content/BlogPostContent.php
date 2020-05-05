<?php


namespace nyansapow\content;


use nyansapow\sites\FrontMatterReader;
use nyansapow\text\TextConverter;
use nyansapow\text\TemplateEngine;
use nyansapow\utils\Nomenclature;

class BlogPostContent extends MarkupContent implements ThemableInterface, SerialContentInterface
{
    use Nomenclature;

    private $templateData = [];
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
     * @var TextConverter
     */
    private $textConverter;
    private $templateEngine;
    private $rendered;
    private $preview;
    private $siteTaxonomies = [];

    public function __construct(TemplateEngine $templateEngine, TextConverter $htmlRenderer, FrontMatterReader $frontMatterReader, $document, $destination)
    {
        parent::__construct($htmlRenderer, $frontMatterReader, $document, $destination);
        $this->textConverter = $htmlRenderer;
        $this->templateEngine = $templateEngine;
    }

    public function getMetaData() : array
    {
        if(!$this->metaData) {
            preg_match(
            "|((?<year>[0-9]{4})/(?<month>[0-9]{2})/(?<day>[0-9]{2})/)?(?<title>[A-Za-z0-9\-\_]*)\.(html)|",
                $this->getDestination(), $matches);
            $frontMatter = $this->getFrontMatter();
            $this->metaData = [
                'title' => $frontMatter['title'] ?? ucfirst(str_replace("-", " ", $matches['title'])),
                'date' => isset($matches['year'])
                    ? date("jS F Y", strtotime("{$matches['year']}-{$matches['month']}-{$matches['day']}"))
                    : "",
                'frontmatter' => $frontMatter,
                'path' => $this->getDestination(),
                'home_path' => $this->templateData['home_path'],
                'site_path' => $this->templateData['site_path']
            ];
        }
        return $this->metaData;
    }

    private function getTaxonomyLinks()
    {
        $links = [];
        $taxonomies = $this->siteTaxonomies;

        foreach($taxonomies as $taxonomy => $taxonomyLabel) {
            if(!isset($this->metaData['frontmatter'][$taxonomy])) {
                continue;
            }
            $taxonomyValues = $this->metaData['frontmatter'][$taxonomy];
            $taxonomyValues = is_array($taxonomyValues) ? $taxonomyValues : [$taxonomyValues];
            $links[$taxonomy] = [];
            foreach($taxonomyValues as $taxonomyValue) {
                $links[$taxonomy][] = ['value' => $taxonomyValue, 'link' => "$taxonomy/{$this->makeId($taxonomyValue)}.html"];
            }
        }

        return $links;
    }

    public function setTemplateData($templateData)
    {
        $this->templateData = $templateData;
    }

    public function setSiteTaxonomies($siteTaxonomies)
    {
        $this->siteTaxonomies = $siteTaxonomies;
    }

    public function render(): string
    {
        if(!$this->rendered) {
            $nextPost = $this->next ? $this->next->getMetaData() : [];
            $prevPost = $this->previous ? $this->previous->getMetaData() : [];
            $this->rendered = $this->templateEngine->render($this->template,
                array_merge(
                    ['body' => parent::render(), 'page_type' => 'post', 'next' => $nextPost, 'prev' => $prevPost],
                    $this->getMetaData(), ['taxonomy' => $this->getTaxonomyLinks()]
                )
            );
        }
        return $this->rendered;
    }

    public function getPreview() : string
    {
        if(!$this->preview) {
            $splitPost = $this->splitPost();
            $format = pathinfo($this->document, PATHINFO_EXTENSION);
            $this->preview = $this->textConverter->convert($splitPost['preview'], $format, 'html');
        }
        return $this->preview;
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
