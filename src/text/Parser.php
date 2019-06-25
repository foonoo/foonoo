<?php

namespace nyansapow\text;

use nyansapow\TocRequestedException;

/**
 * Parse text containing special Nyansapow tags.
 *
 * @package nyansapow
 */
class Parser
{
    /**
     * A path to the website base.
     *
     * @var string
     */
    private $pathToBase;

    private $pages = [];
    private $tocTrigger;
    private $teplateEngine;
    private $tocGenerator;

    /**
     * All the regular expressions
     * @var array
     */
    private $regexes = [
        // Match gollum style TOC so that github wikis can be rendered //
        'pre' => [['regex' => "/\[\[_TOC_\]\]/", 'method' => 'renderTableOfContents']],
        'post' => [

            // Match special nyansapow blocs
            ['regex' => "/\[\[nyansapow\:(?<content>[a-zA-Z0-9\_]*)\]\]/", 'method' => 'renderNyansapowContent'],

            // Match begining and ending of special blocks
            ['regex' => "/\[\[block\:(?<block_class>[a-zA-Z0-9\-\_]*)\]\]/", 'method' => "renderBlockOpenTag"],

            // Match begining and ending of special blocks
            ['regex' => "/\[\[\/block\]\]/", 'method' => "renderBlockCloseTag"],

            // Match http links [[http://example.com]]
            ['regex' => "/\[\[(http:\/\/)(?<link>.*)\]\]/", 'method' => "renderLink"],

            // Match images [[something.imgext|Alt Text|options]]
            [
                'regex' => "/\[\[(?<image>.*\.(jpeg|jpg|png|gif|webp))\s*(\|'?(?<alt>[a-zA-Z0-9 ,.-]*)'?)?(?<options>[a-zA-Z0-9_=|:%]+)?\]\]/",
                'method' => "renderImageTag"
            ],

            // Match page links [[Page Link]]
            ['regex' => "|\[\[(?<markup>[a-zA-Z0-9 ]*)\]\]|", 'method' => "renderPageLink"],

            // Match page links [[Title|Page Link]]
            ['regex' => "|\[\[(?<title>[a-zA-Z0-9 ]*)\|(?<markup>[a-zA-Z0-9 ]*)\]\]|", 'method' => "renderPageLink"],
        ]
    ];

    public function __construct(TemplateEngine $templateEngine, TocGenerator $tocGenerator)
    {
        $this->teplateEngine = $templateEngine;
        $this->tocGenerator = $tocGenerator;
    }

    public function domCreated($dom)
    {
        $this->tocGenerator->domCreated($dom);
    }

    public function preParse($content)
    {
        return $this->parse($content, 'pre');
    }

    public function postParse($content, $tocTrigger = true)
    {
       $this->$tocTrigger = $tocTrigger;
        return$this->parse($content, 'post');
    }

    private function parse($content, $mode)
    {
        $parsed = '';
        foreach (explode("\n", $content) as $line) {
            $parsed .= $this->parseLine($line, $mode) . "\n";
        }
        return $parsed;
    }

    private function parseLine($line, $mode)
    {
        foreach ($this->regexes[$mode] as $regex) {
            $line = preg_replace_callback($regex['regex'], [$this, $regex['method']], $line);
        }

        return $line;
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

    private function renderImageTag($matches)
    {
        $attributes =$this->getImageTagAttributes($matches['options'] ?? '');
        $attributeString = '';
        foreach ($attributes as $key => $value) {
            $attributeString .= "$key = '$value' ";
        }
        return $this->teplateEngine->render('image_tag',
            [
                'alt' => $matches['alt'] ?? '',
                'path_to_base' => $this->pathToBase,
                'images' => $this->getImages($matches['image']),
                'attribute_string' => $attributeString
            ]
        );
    }

    private function renderPageLink($matches)
    {
        $link = str_replace(array(' ', '/'), '-', $matches['markup']);
        foreach ($this->pages as $page) {
            if (strtolower($page['page']) == strtolower($link)) {
                return $this->teplateEngine->render('anchor_tag', [
                    'href' => "{$page['page']}.html",
                    'link_text' => isset($matches['title']) ? $matches['title'] : $matches['markup']
                ]);
            }
        }
        return $matches['markup'];
    }

    private function renderLink($matches)
    {
        return $this->teplateEngine->render('anchor_tag', [
            'href' => "http://{$matches['link']}",
            'link_text' => "http://{$matches['link']}"
        ]);
    }

    private function renderBlockOpenTag($matches)
    {
        return $this->teplateEngine->render('block_open_tag', ['block' => $matches['block_class']]);
    }

    private function renderBlockCloseTag($matches)
    {
        return $this->teplateEngine->render('block_close_tag', []);
    }

    private function renderTableOfContents($matches)
    {
        $this->tocGenerator->hasToc = true;
        return "[[nyansapow:toc]]";
    }

    private function getTableOfContents()
    {
        return $this->tocGenerator->getTableOfContents();
    }

    private function renderNyansapowContent($matches)
    {
        switch ($matches['content']) {
            case 'toc':
                if ($this->tocTrigger) {
                    throw new TocRequestedException();
                }
                return $this->tocGenerator->renderTableOfContents();
        }
    }

    public function setPathToBase($pathToBase)
    {
       $this->pathToBase = $pathToBase;
    }

    public function setPages($pages)
    {
       $this->pages = $pages;
    }
}
