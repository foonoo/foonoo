<?php

namespace nyansapow\text;


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

    public function __construct(TemplateEngine $templateEngine, TocGenerator $tocGenerator)
    {
        $this->templateEngine = $templateEngine;
        $this->tocGenerator = $tocGenerator;
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
        return function ($page) use ($method) {
            return function ($matches) use ($method, $page) {
                return $method($matches, $page);
            };
        };
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

    private function getImages($string)
    {
        preg_match_all("/((?<image>.*\.(jpeg|jpg|png|gif|webp))\s*,\s*)*(?<last_image>.*\.(jpeg|jpg|png|gif|webp))/", $string, $matches);
        $images = array_filter(array_merge($matches['image'], $matches['last_image']), function($item){return $item !== "";});
        return $images;
    }

    /**
     * Renders an image tag.
     *
     * @param $matches
     * @param AbstractSite $site
     * @param $page
     * @return string
     */
    private function renderImageTag(array $matches, AbstractSite $site, Content $page)
    {
        $attributes =$this->getImageTagAttributes($matches['options'] ?? '');
        $attributeString = '';
        foreach ($attributes as $key => $value) {
            $attributeString .= "$key = '$value' ";
        }
        $templateVariables = $site->getTemplateData($site->getDestinationPath($page->getDestination()));
        return $this->templateEngine->render('image_tag',
            [
                'alt' => $matches['alt'] ?? '',
                'site_path' => $templateVariables['site_path'],
                'home_path' => $templateVariables['home_path'],
                'images' => $this->getImages($matches['image']),
                'attribute_string' => $attributeString
            ]
        );
    }

    private function renderPageLink(array $matches, AbstractSite $site, Content $page)
    {
        $templateVariables = $site->getTemplateData($site->getDestinationPath($page->getDestination()));
        $link = strtolower($matches['markup']);
        foreach ($site->getPages() as $targetPage) {
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

    private function renderLink(array $matches)
    {
        return $this->templateEngine->render('anchor_tag', [
            'href' => "http://{$matches['link']}",
            'link_text' => "http://{$matches['link']}"
        ]);
    }

    private function renderBlockOpenTag(array $matches)
    {
        return $this->templateEngine->render('block_open_tag', ['block' => $matches['block_class']]);
    }

    private function renderBlockCloseTag()
    {
        return $this->templateEngine->render('block_close_tag', []);
    }

    private function renderTableOfContents(array $matches, AbstractSite $site, Content $page)
    {
        $tocTree = $this->tocGenerator->get($page);
        if($tocTree) {
            return $this->templateEngine->render('table_of_contents_tag', ['tree' => $tocTree]);
        }
    }
}
