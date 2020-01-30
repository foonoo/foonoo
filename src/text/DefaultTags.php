<?php

namespace nyansapow\text;


use nyansapow\content\PreprocessableInterface;
use nyansapow\events\EventDispatcher;
use nyansapow\events\PageWriteStarted;
use nyansapow\events\SiteWriteStarted;
use nyansapow\sites\AbstractSite;
use nyansapow\content\Content;
use nyansapow\utils\Nomenclature;
use nyansapow\utils\TocGenerator;

/**
 * Renders the preprocessing tags.
 *
 * @package nyansapow\text
 */
class DefaultTags
{
    use Nomenclature;

    private $templateEngine;
    private $tocGenerator;
    private $data;
    private $site;
    private $templateData;

    public function __construct(TemplateEngine $templateEngine, TocGenerator $tocGenerator, EventDispatcher $eventDispatcher)
    {
        $this->templateEngine = $templateEngine;
        $this->tocGenerator = $tocGenerator;
        $eventDispatcher->addListener(SiteWriteStarted::class,
            function (SiteWriteStarted $event) {
                $this->site = $event->getSite();
            }
        );
        $eventDispatcher->addListener(PageWriteStarted::class,
            function (PageWriteStarted $event) {
                $this->templateData = $this->site->getTemplateData($event->getContent()->getFullDestination());
            }
        );
    }

    public function getRegexMap()
    {
        return [
            [
                "regex" => "/block\:(?<block_class>[a-zA-Z0-9\-\_]*)/",
                "callable" => $this->getCallback([$this, "renderBlockOpenTag"])
            ],
            ["regex" => "/\/block/", "callable" => $this->getCallback([$this, "renderBlockOpenTag"])],
            ["regex" => "/(http:\/\/)(?<link>.*)/", "callable" => $this->getCallback([$this, "renderLink"])],
            [
                "regex" => "/(?<image>.*\.(jpeg|jpg|png|gif|webp))\s*(\|'?(?<alt>[a-zA-Z0-9 ,.-]*)'?)?(?<options>[a-zA-Z0-9_=|:%]+)?/",
                "callable" => $this->getCallback([$this, "renderImageTag"])
            ],
            ["regex" => "|(?<markup>[a-zA-Z0-9 _\-.]*)|", "callable" => $this->getCallback([$this, "renderPageLink"])],
            ["regex" => "|(?<title>[a-zA-Z0-9 _\-.]*)\|(?<markup>[a-zA-Z0-9 _\-.]*)|", "callable" => $this->getCallback([$this, "renderPageLink"])],
            ['regex' => "/_TOC_/", 'callable' => $this->getCallback([$this, 'renderTableOfContents'])],
        ];
    }

    private function getCallback($method)
    {
        return $method;
//        return function ($matches) use ($method) {
//            return $method($matches);
//        };
    }

    private function getImageTagAttributes($string)
    {
        preg_match_all("/(\|((?<attribute>[a-zA-Z0-9]+)(:(?<value>[a-zA-Z0-9]*))?))/", $string, $matches);
        $attributes = array();
        foreach ($matches['attribute'] as $key => $attribute) {
            if ($matches['value'][$key] == '') {
                $attributes[$attribute] = true;
            } else {
                $attributes[$attribute] = $matches['value'][$key];
            }
        }

        return $attributes;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    private function getImages($string)
    {
        preg_match_all("/((?<image>.*\.(jpeg|jpg|png|gif|webp))\s*,\s*)*(?<last_image>.*\.(jpeg|jpg|png|gif|webp))/", $string, $matches);
        $images = array_filter(array_merge($matches['image'], $matches['last_image']), function($item){return $item !== "";});
        return $images;
    }

    /**
     * Renders an image tag.
     *
     * @param array $matches
     * @return string
     */
    public function renderImageTag(array $matches) //, AbstractSite $site, Content $page)
    {
        $attributes =$this->getImageTagAttributes($matches['options'] ?? '');
        $attributeString = '';
        foreach ($attributes as $key => $value) {
            $attributeString .= "$key = '$value' ";
        }
        //$templateVariables = $site->getTemplateData($site->getDestinationPath($page->getDestination()));
        return $this->templateEngine->render('image_tag',
            [
                'alt' => $matches['alt'] ?? '',
                'site_path' => $this->data['site_path'], // $templateVariables['site_path'],
                'home_path' => $this->data['home_path'], // $templateVariables['home_path'],
                'images' => $this->getImages($matches['image']),
                'attribute_string' => $attributeString
            ]
        );
    }

    public function renderPageLink(array $matches) //, AbstractSite $site, Content $page)
    {
        //$templateVariables = $site->getTemplateData($site->getDestinationPath($page->getDestination()));
        $link = strtolower($matches['markup']);
        foreach ($this->site->getPages() as $targetPage) {
            $title = $targetPage->getMetaData()['title']
                   ?? $this->makeLabel(pathinfo($targetPage->getDestination(), PATHINFO_FILENAME));
            if (strtolower($title) == $link) {
                return $this->templateEngine->render('anchor_tag', [
                    'href' => "{$templateVariables['site_path']}{$targetPage->getDestination()}",
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

    public function renderTableOfContents(array $matches, AbstractSite $site, Content $page)
    {
        $tocTree = $this->tocGenerator->get($page);
        if($tocTree) {
            return $this->templateEngine->render('table_of_contents_tag', ['tree' => $tocTree]);
        }
    }
}
