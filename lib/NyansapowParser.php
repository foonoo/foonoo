<?php
/**
 * 
 */
class NyansapowParser
{
    private static $nyansapow;
    
    private static $regexes = array(
        'pre' => array(
            array(
                'regex' => "/\[\[_TOC_\]\]/",
                'method' => 'NyansapowParser::renderTableOfContents'
            )
        ),
        'post' => array(
            
            // Match begining and ending of special blocks
            array(
                'regex' => "/\[\[block\:(?<block_class>[a-zA-Z0-9\-\_]*)\]\]/", 
                'method' => "NyansapowParser::renderBlockOpenTag"
            ),
        
            // Match begining and ending of special blocks
            array(
                'regex' => "/\[\[\/block\]\]/", 
                'method' => "NyansapowParser::renderBlockCloseTag"
            ),
                
            // Match http links [[http://example.com]]
            array(
                'regex' => "/\[\[(http:\/\/)(?<link>.*)\]\]/",
                'method' => "NyansapowParser::renderLink"
            ),
        
            // Match images [[something.imgext|Alt Text|options]]
            array(
                'regex' => "/\[\[(?<image>.*\.(jpeg|jpg|png|gif))(\|'?(?<alt>[a-zA-Z0-9 ]*)'?)?(?<options>[a-zA-Z_=|:]+)?\]\]/",
                'method' => "NyansapowParser::renderImageTag"
            ),
        
            // Match page links [[Page Link]]
            array(
                'regex' => "|\[\[(?<markup>[a-zA-Z0-9 ]*)\]\]|",
                'method' => "NyansapowParser::renderPageLink"
            ),
        
            // Match page links [[Title|Page Link]]
            array(
                'regex' => "|\[\[(?<title>[a-zA-Z0-9 ]*)\|(?<markup>[a-zA-Z0-9 ]*)\]\]|",
                'method' => "NyansapowParser::renderPageLink"
            )
            
        )
    );
    
    public static function setNyansapow($nyansapow)
    {
        self::$nyansapow = $nyansapow;
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
            $parsed .= NyansapowParser::parseLine($line, $mode) . "\n";
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
        $style = "";
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
        
        $style = $style == "" ? '' : "style='$style'";
        return "{$frameOpen}<img $style src='images/{$matches['image']}' alt='{$matches['alt']}' />{$frameClose}";
    }
    
    public static function renderPageLink($matches)
    {
        $link = str_replace(array(' ', '/'), '-', $matches['markup']);
        foreach(self::$nyansapow->getPages() as $page)
        {
            if(strtolower($page) == strtolower($link))
            {
                return "<a href='{$page}.html'>" .(isset($matches['title']) ? $matches['title'] : $matches['markup']) . "</a>";
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
        var_dump($matches[0]);
        $toc = self::$nyansapow->getTableOfContents();
        return "Hello!";
    }
}
