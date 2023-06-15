<?php

namespace foonoo\text;


use foonoo\events\EventDispatcher;
use foonoo\events\ContentGenerationStarted;
use foonoo\events\SiteWriteStarted;
use foonoo\sites\AbstractSite;
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
    private $contentID;

    public function __construct(TemplateEngine $templateEngine, TocGenerator $tocGenerator, EventDispatcher $eventDispatcher)
    {
        $this->templateEngine = $templateEngine;
        $this->tocGenerator = $tocGenerator;
        $eventDispatcher->addListener(SiteWriteStarted::class,
            function (SiteWriteStarted $event) {
                $this->site = $event->getSite();
            }
        );
        $eventDispatcher->addListener(ContentGenerationStarted::class,
            function (ContentGenerationStarted $event) {
                $this->contentID = $event->getContent()->getID();
                $this->templateData = $this->site->getTemplateData($event->getContent()->getFullDestination());
            }
        );
    }

    public function getRegexMap()
    {
        $httpLinkRegex = "(?<protocol>[a-z]+:\/\/)(?<link>.*)";
        $imgLinkRegex = "(?<image>.*\.(jpeg|jpg|png|gif|webp))";

        return [
            ["regex" => ["description" => TagToken::TEXT, "page" => TagToken::TEXT], "callable" => [$this, "renderPageLink"], 'name' => 'page link'],
            ["regex" => ["page" => TagToken::TEXT], "callable" => [$this, "renderPageLink"], 'name' => 'page link'],
            ["regex" => ["description" => TagToken::TEXT, "link" => $httpLinkRegex], "callable" => [$this, "renderLink"], 'name' => 'http link '],
            ["regex" => ["link" => $httpLinkRegex], "callable" => [$this, "renderLink"], 'name' => 'http link '],
            ["regex" => ["link" => $imgLinkRegex], "callable" => [$this, "renderImageTag"], 'name' => 'image'],
            ["regex" => ["alt" => TagToken::TEXT, "link" => $imgLinkRegex], "callable" => [$this, "renderImageTag"], 'name' => 'image'],
            ["regex" => ["\/block"], "callable" => [$this, "renderBlockCloseTag"], "name" => 'close block'],
            ["regex" => ["block\:(?<block_class>[a-zA-Z0-9\-\_]*)"], "callable" => [$this, "renderBlockOpenTag"], 'name' => 'open block'],
            ['regex' => ["_TOC_"], 'callable' => [$this, 'renderTableOfContents'], 'name' => 'table of contents'],
        ];
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Renders an image tag.
     *
     * @param array $matches
     * @return string
     */
    public function renderImageTag(array $args)
    {
        $matchedLink = $args["link"];
        return trim($this->templateEngine->render('image_tag',
            [
                'alt' => $args['alt'] ?? '',
                'site_path' => $this->templateData['site_path'] ?? '',
                'home_path' => $this->templateData['home_path'] ?? '',
                'image' => $matchedLink['image'],
                'attributes' => $args["__attr"] ?? array()
            ]
        ));
    }

    public function renderPageLink(array $matches, string $passThrough)
    {
        $link = strtolower($matches['page']);
        foreach ($this->site->getContent() as $targetPage) {
            $title = $targetPage->getMetaData()['frontmatter']['title']
                   ?? $this->makeLabel(pathinfo($targetPage->getDestination(), PATHINFO_FILENAME));
            if (strtolower($title) == $link) {
                return $this->templateEngine->render('anchor_tag', [
                    'href' => $this->templateData['site_path'] . $targetPage->getDestination(),
                    'link_text' => $matches['description'] ?? $matches['page']
                ]);
            }
        }
        return "[[{$passThrough}]]";
    }

    public function renderLink(array $matches, string $text, array $args)
    {
        return $this->templateEngine->render('anchor_tag', [
            'href' => "{$matches['protocol']}//{$matches['link']}",
            'link_text' => $args['__default'] ?? "{$matches['protocol']}//{$matches['link']}"
                
        ]);
    }

    public function renderBlockOpenTag(array $args)
    {
        return $this->templateEngine->render('block_open_tag', ['block' => $args[0]['block_class']]);
    }

    public function renderBlockCloseTag()
    {
        return $this->templateEngine->render('block_close_tag', []);
    }

    public function renderTableOfContents()
    {
        return $this->tocGenerator->createContainer($this->contentID);
    }
}
