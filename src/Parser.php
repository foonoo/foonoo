<?php

namespace nyansapow;

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
    private static $pathToBase;

    /**
     * @var
     */
    private static $typeIndex;
    private static $pages = [];
    private static $tocTrigger;

    /**
     * All the regular expressions
     * @var array
     */
    private static $regexes = array(
        // Match gollum style TOC so that github wikis can be rendered //
        'pre' => array(
            array(
                'regex' => "/\[\[_TOC_\]\]/",
                'method' => '\\nyansapow\\Parser::renderTableOfContents'
            )
        ),
        'post' => array(

            // Match special nyansapow blocs
            array(
                'regex' => "/\[\[nyansapow\:(?<content>[a-zA-Z0-9\_]*)\]\]/",
                'method' => '\\nyansapow\\Parser::renderNyansapowContent'
            ),

            // Match begining and ending of special blocks
            array(
                'regex' => "/\[\[block\:(?<block_class>[a-zA-Z0-9\-\_]*)\]\]/",
                'method' => "\\nyansapow\\Parser::renderBlockOpenTag"
            ),

            // Match begining and ending of special blocks
            array(
                'regex' => "/\[\[\/block\]\]/",
                'method' => "\\nyansapow\\Parser::renderBlockCloseTag"
            ),

            // Match http links [[http://example.com]]
            array(
                'regex' => "/\[\[(http:\/\/)(?<link>.*)\]\]/",
                'method' => "\\nyansapow\\Parser::renderLink"
            ),

            // Match images [[something.imgext|Alt Text|options]]
            array(
                'regex' => "/\[\[(?<image>.*\.(jpeg|jpg|png|gif))(\|'?(?<alt>[a-zA-Z0-9 ]*)'?)?(?<options>[a-zA-Z0-9_=|:%]+)?\]\]/",
                'method' => "\\nyansapow\\Parser::renderImageTag"
            ),

            // Match page links [[Page Link]]
            array(
                'regex' => "|\[\[(?<markup>[a-zA-Z0-9 ]*)\]\]|",
                'method' => "\\nyansapow\\Parser::renderPageLink"
            ),

            // Match page links [[Title|Page Link]]
            array(
                'regex' => "|\[\[(?<title>[a-zA-Z0-9 ]*)\|(?<markup>[a-zA-Z0-9 ]*)\]\]|",
                'method' => "\\nyansapow\\Parser::renderPageLink"
            ),

            // Match PHP object types
            array(
                'regex' => "|(([a-zA-Z0-9_]+)?(\\\\[a-zA-Z0-9_]+)+)|",
                'method' => "\\nyansapow\\Parser::renderPHPType"
            )
        )
    );

    public static function setProcessor($wiki)
    {
        self::$wiki = $wiki;
    }

    public static function domCreated($dom)
    {
        TocGenerator::domCreated($dom);
    }

    public static function preParse($content)
    {
        return self::parse($content, 'pre');
    }

    public static function postParse($content, $tocTrigger = true)
    {
        self::$tocTrigger = $tocTrigger;
        return self::parse($content, 'post');
    }

    private static function parse($content, $mode)
    {
        $parsed = '';
        foreach (explode("\n", $content) as $line) {
            $parsed .= Parser::parseLine($line, $mode) . "\n";
        }
        return $parsed;
    }

    private static function parseLine($line, $mode)
    {
        foreach (self::$regexes[$mode] as $regex) {
            $line = preg_replace_callback(
                $regex['regex'],
                $regex['method'],
                $line
            );
        }

        return $line;
    }

    public static function renderTag($matches)
    {

    }

    public static function getImageTagAttributes($string)
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

    public static function renderPHPType($matches)
    {
        $path = "";
        if (isset(self::$typeIndex[$matches[0]])) {
            $path = self::$typeIndex[$matches[0]];
        } else if (isset(self::$typeIndex[substr($matches[0], 1)])) {
            $path = self::$typeIndex[substr($matches[0], 1)];
        }
        return "<a href='" . self::$pathToBase . "{$path}'>{$matches[0]}</a>";
    }

    public static function renderImageTag($matches)
    {
        $attributes = self::getImageTagAttributes($matches['options'] ?? '');
        $frameOpen = $frameClose = $style = '';
        $caption = '';
        $alt = '';

        if (!isset($attributes['no-frame'])) {
            $frameStyle = "";
            if (isset($matches['alt'])) {
                $caption = "<span class='img-caption'>{$matches['alt']}</span>";
                $alt = $matches['alt'];
            }
            $frameOpen = "<div class='img-wrapper'><div class='img-frame' $frameStyle >";
            $frameClose = "\n$caption</div></div>";
        }
        unset($attributes['no-frame']);

        $attributeString = '';
        foreach ($attributes as $key => $value) {
            $attributeString .= "$key = '$value' ";
        }

        return "{$frameOpen}<img $style src='" . self::$pathToBase . "np_images/{$matches['image']}' alt='{$alt}' $attributeString />{$frameClose}";
    }

    public static function renderPageLink($matches)
    {
        $link = str_replace(array(' ', '/'), '-', $matches['markup']);
        foreach (self::$pages as $page) {
            if (strtolower($page['page']) == strtolower($link)) {
                return "<a href='{$page['page']}.html'>" . (isset($matches['title']) ? $matches['title'] : $matches['markup']) . "</a>";
            }
        }
        return $matches['markup'];
    }

    public static function renderLink($matches)
    {
        return "<a href='http://{$matches['link']}'>http://{$matches['link']}</a>";
    }

    public static function renderBlockOpenTag($matches)
    {
        return "<div class='block {$matches['block_class']}'>";
    }

    public static function renderBlockCloseTag($matches)
    {
        return "</div>";
    }

    public static function renderTableOfContents($matches)
    {
        TocGenerator::$hasToc = true;
        return "[[nyansapow:toc]]";
    }

    public static function getTableOfContents()
    {
        return TocGenerator::getTableOfContents();
    }

    public static function renderNyansapowContent($matches)
    {
        switch ($matches['content']) {
            case 'toc':
                if (self::$tocTrigger) {
                    throw new TocRequestedException();
                }
                return TocGenerator::renderTableOfContents();
        }
    }

    public static function setPathToBase($pathToBase)
    {
        self::$pathToBase = $pathToBase;
    }

    public static function setTypeIndex($typeIndex)
    {
        self::$typeIndex = $typeIndex;
    }

    public static function setPages($pages)
    {
        self::$pages = $pages;
    }
}
