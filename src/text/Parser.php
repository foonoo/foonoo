<?php

namespace nyansapow\text;

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

    /**
     * @var
     */
    private $typeIndex;
    private $pages = [];
    private $tocTrigger;

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
                'regex' => "/\[\[(?<image>.*\.(jpeg|jpg|png|gif|webp))(\|'?(?<alt>[a-zA-Z0-9 ]*)'?)?(?<options>[a-zA-Z0-9_=|:%]+)?\]\]/",
                'method' => "renderImageTag"
            ],

            // Match page links [[Page Link]]
            ['regex' => "|\[\[(?<markup>[a-zA-Z0-9 ]*)\]\]|", 'method' => "renderPageLink"],

            // Match page links [[Title|Page Link]]
            ['regex' => "|\[\[(?<title>[a-zA-Z0-9 ]*)\|(?<markup>[a-zA-Z0-9 ]*)\]\]|", 'method' => "renderPageLink"],

            // Match PHP object types
            ['regex' => "|(([a-zA-Z0-9_]+)?(\\\\[a-zA-Z0-9_]+)+)|", 'method' => "renderPHPType"]
        ]
    ];

    public function setProcessor($wiki)
    {
       $this->$wiki = $wiki;
    }

    public function domCreated($dom)
    {
        TocGenerator::domCreated($dom);
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
            $parsed .= Parser::parseLine($line, $mode) . "\n";
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

    public function getImageTagAttributes($string)
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

    public function renderPHPType($matches)
    {
        $path = "";
        if (isset(self::$typeIndex[$matches[0]])) {
            $path =$this->typeIndex[$matches[0]];
        } else if (isset(self::$typeIndex[substr($matches[0], 1)])) {
            $path =$this->typeIndex[substr($matches[0], 1)];
        }
        return "<a href='" .$this->pathToBase . "{$path}'>{$matches[0]}</a>";
    }

    public function renderImageTag($matches)
    {
        $attributes =$this->getImageTagAttributes($matches['options'] ?? '');
        $attributeString = '';
        $alt = $matches['alt'] ?? '';
        foreach ($attributes as $key => $value) {
            $attributeString .= "$key = '$value' ";
        }

        return "<img src='" .$this->pathToBase . "np_images/{$matches['image']}' alt='{$alt}' $attributeString />";
    }

    public function renderPageLink($matches)
    {
        $link = str_replace(array(' ', '/'), '-', $matches['markup']);
        foreach (self::$pages as $page) {
            if (strtolower($page['page']) == strtolower($link)) {
                return "<a href='{$page['page']}.html'>" . (isset($matches['title']) ? $matches['title'] : $matches['markup']) . "</a>";
            }
        }
        return $matches['markup'];
    }

    public function renderLink($matches)
    {
        return "<a href='http://{$matches['link']}'>http://{$matches['link']}</a>";
    }

    public function renderBlockOpenTag($matches)
    {
        return "<div class='block {$matches['block_class']}'>";
    }

    public function renderBlockCloseTag($matches)
    {
        return "</div>";
    }

    public function renderTableOfContents($matches)
    {
        TocGenerator::$hasToc = true;
        return "[[nyansapow:toc]]";
    }

    public function getTableOfContents()
    {
        return TocGenerator::getTableOfContents();
    }

    public function renderNyansapowContent($matches)
    {
        switch ($matches['content']) {
            case 'toc':
                if (self::$tocTrigger) {
                    throw new TocRequestedException();
                }
                return TocGenerator::renderTableOfContents();
        }
    }

    public function setPathToBase($pathToBase)
    {
       $this->$pathToBase = $pathToBase;
    }

    public function setTypeIndex($typeIndex)
    {
       $this->$typeIndex = $typeIndex;
    }

    public function setPages($pages)
    {
       $this->$pages = $pages;
    }
}
