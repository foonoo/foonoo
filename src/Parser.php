<?php

namespace nyansapow;

class Parser
{
    public static $dom;
    private static $pathToBase;
    
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
            )
        )
    );
    
    public static function setProcessor($wiki)
    {
        self::$wiki = $wiki;
    }
    
    public static function domCreated($dom)
    {
        self::$dom = $dom;
        TocGenerator::domCreated();
    }
    
    public static function preParse($content)
    {
        return self::parse($content, 'pre');
    }
    
    public static function postParse($content)
    {
        return self::parse($content, 'post');
    }
    
    private static function parse($content, $mode)
    {
        $parsed = '';
        foreach(explode("\n", $content) as $line)
        {
            $parsed .= Parser::parseLine($line, $mode) . "\n";
        }
        return $parsed;
    }
    
    private static function parseLine($line, $mode)
    { 
        foreach(self::$regexes[$mode] as $regex)
        {
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
        foreach($matches['attribute'] as $key => $attribute)
        {
            if($matches['value'][$key] == '')
            {
                $attributes[$attribute] = true;
            }
            else
            {
                $attributes[$attribute] = $matches['value'][$key];
            }
        }
        
        return $attributes;
    }
    
    public static function renderImageTag($matches)
    {
        $attributes = self::getImageTagAttributes($matches['options']);
        if($attributes['float'])
        {
            if($attributes['align'] == 'right')
            {
                $style .= 'float:right;';
            }
            else
            {
                $style .= 'float:left;';
            }
            unset($attributes['float']);
        }
        
        
        if($attributes['frame'])
        {
            if($attributes['align'] == 'center')
            {
                $frameStyle = "style='text-align:center'";
            }
            if($matches['alt'] != '')
            {
                $caption = "<div class='img-caption'>{$matches['alt']}</div>";
            }
            $frameOpen = "<div class='img-frame' $frameStyle >";
            $frameClose = "$caption</div>";
        }
        unset($attributes['align']);
        unset($attributes['frame']);
        
        $attributeString = '';
        foreach($attributes as $key => $value)
        {
            $attributeString .= "$key = '$value' ";
        }
        
        $style = $style == "" ? '' : "style='$style'";
        return "{$frameOpen}<img $style src='" . self::$pathToBase . "np_images/{$matches['image']}' alt='{$matches['alt']}' $attributeString />{$frameClose}";
    }
    
    public static function renderPageLink($matches)
    {
        $link = str_replace(array(' ', '/'), '-', $matches['markup']);
        foreach(self::$wiki->getPages() as $page)
        {
            if(strtolower($page['page']) == strtolower($link))
            {
                return "<a href='{$page['page']}.html'>" .(isset($matches['title']) ? $matches['title'] : $matches['markup']) . "</a>";
            }
        }
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
    
    public static function renderNyansapowContent($matches)
    {
        switch($matches['content'])
        {
            case 'toc':
                return TocGenerator::renderTableOfContents();
        }
    }
    
    public static function setPathToBase($pathToBase)
    {
        self::$pathToBase = $pathToBase;
    }
}
