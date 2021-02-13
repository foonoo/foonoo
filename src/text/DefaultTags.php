<?php

namespace foonoo\text;


use foonoo\events\EventDispatcher;
use foonoo\events\ContentWriteStarted;
use foonoo\events\SiteWriteStarted;
use foonoo\sites\AbstractSite;
use foonoo\content\Content;
use foonoo\utils\Nomenclature;

/**
 * Renders the preprocessing tags.
 *
 * @package nyansapow\text
 */
class DefaultTags
{
    use Nomenclature;

    /**
     * @var TemplateEngine
     */
    private $templateEngine;

    /**
     * @var TocGenerator
     */
    private $tocGenerator;

    /**
     * @var array
     */
    private $data;

    /**
     * @var AbstractSite
     */
    private $site;

    /**
     * @var array
     */
    private $templateData;

    /**
     * @var string
     */
    private $pageDestination;

    public function __construct(TemplateEngine $templateEngine, TocGenerator $tocGenerator, EventDispatcher $eventDispatcher)
    {
        $this->templateEngine = $templateEngine;
        $this->tocGenerator = $tocGenerator;
        $eventDispatcher->addListener(SiteWriteStarted::class,
            function (SiteWriteStarted $event) {
                $this->site = $event->getSite();
            }
        );
        $eventDispatcher->addListener(ContentWriteStarted::class,
            function (ContentWriteStarted $event) {
                $this->pageDestination = $event->getContent()->getDestination();
                $this->templateData = $this->site->getTemplateData($event->getContent()->getFullDestination());
            }
        );
    }

    public function getRegexMap()
    {
        return [
            ["regex" => "/block\:(?<block_class>[a-zA-Z0-9\-\_]*)/", "callable" => [$this, "renderBlockOpenTag"], 'name' => 'open block'],
            ["regex" => "/\/block/", "callable" => [$this, "renderBlockCloseTag"], "name" => 'close block'],
            ["regex" => "/(http:\/\/)(?<link>.*)/", "callable" => [$this, "renderLink"], 'name' => 'http link '],
            ["regex" => "/(?<image>.*\.(jpeg|jpg|png|gif|webp))/", "callable" => [$this, "renderImageTag"], 'name' => 'name'],
            ['regex' => "/_TOC_/", 'callable' => [$this, 'renderTableOfContents'], 'name' => 'table of contents'],
            ["regex" => "|(?<markup>[a-zA-Z0-9 _\-.]*)|", "callable" => [$this, "renderPageLink"], 'name' => 'page link'],
        ];
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    private function getImages($string)
    {
        preg_match_all("/((?<image>.*\.(jpeg|jpg|png|gif|webp))\s*,\s*)*(?<last_image>.*\.(jpeg|jpg|png|gif|webp))/", $string, $matches);
        $images = array_values(array_filter(array_merge($matches['image'], $matches['last_image']), function($item){return $item !== "";}));
        return $images;
    }

    /**
     * Renders an image tag.
     *
     * @param array $matches
     * @return string
     */
    public function renderImageTag(array $matches, string $tag, array $attributes)
    {
        return trim($this->templateEngine->render('image_tag',
            [
                'alt' => $matches['alt'] ?? '',
                'site_path' => $this->data['site_path'] ?? '',
                'home_path' => $this->data['home_path'] ?? '',
                'images' => $this->getImages($matches['image']),
                'attributes' => $attributes
            ]
        ));
    }

    public function renderPageLink(array $matches)
    {
        $link = strtolower($matches['markup']);
        foreach ($this->site->getContent() as $targetPage) {
            $title = $targetPage->getMetaData()['title']
                   ?? $this->makeLabel(pathinfo($targetPage->getDestination(), PATHINFO_FILENAME));
            if (strtolower($title) == $link) {
                return $this->templateEngine->render('anchor_tag', [
                    'href' => "{$this->data['site_path']}{$targetPage->getDestination()}",
                    'link_text' => $title
                ]);
            }
        }
        return "[[{$matches['markup']}]]";
    }

    public function renderLink(array $matches)
    {
        return $this->templateEngine->render('anchor_tag', [
            'href' => "http://{$matches['link']}",
            'link_text' => "http://{$matches['link']}"
        ]);
    }

    public function renderBlockOpenTag(array $matches)
    {
        return $this->templateEngine->render('block_open_tag', ['block' => $matches['block_class']]);
    }

    public function renderBlockCloseTag()
    {
        return $this->templateEngine->render('block_close_tag', []);
    }

    public function renderTableOfContents()
    {
        return $this->tocGenerator->anticipate($this->pageDestination);
    }
}
