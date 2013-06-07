<?php
class NyansapowParser
{
    private static $nyansapow;
    
    public static function setNyansapow($nyansapow)
    {
        self::$nyansapow = $nyansapow;
    }
    
    public static function parse($content)
    {
        $parsed = '';
        foreach(explode("\n", $content) as $line)
        {
            $parsed .= NyansapowParser::parseLine($line) . "\n";
        }
        return $parsed;
    }
    
    public static function parseLine($line)
    { 
        // Match begining and ending of special blocks
        $line = preg_replace_callback(
            "/\[\[block\:(?<block_class>[a-zA-Z0-9\-\_]*)\]\]/", 
            "NyansapowParser::renderBlockOpenTag", 
            $line
        );
        
        // Match begining and ending of special blocks
        $line = preg_replace_callback(
            "/\[\[\/block\]\]/", 
            "NyansapowParser::renderBlockCloseTag", 
            $line
        );        
        
        // Match http links [[http://example.com]]
        $line = preg_replace_callback(
            "/\[\[(http:\/\/)(?<link>.*)\]\]/",
            "NyansapowParser::renderLink",
            $line
        );
        
        // Match images [[something.imgext|Alt Text|options]]
        $line = preg_replace_callback(
            "/\[\[(?<image>.*\.(jpeg|jpg|png|gif))(\|'?(?<alt>[a-zA-Z0-9 ]*)'?)?(?<options>[a-zA-Z_=|:]+)?\]\]/",
            "NyansapowParser::renderImageTag",
            $line
        );
        
        // Match page links [[Page Link]]
        $line =  preg_replace_callback(
            "|\[\[(?<markup>[a-zA-Z0-9 ]*)\]\]|",
            "NyansapowParser::renderPageLink",
            $line
        );
        
        // Match page links [[Title|Page Link]]
        $line =  preg_replace_callback(
            "|\[\[(?<title>[a-zA-Z0-9 ]*)\|(?<markup>[a-zA-Z0-9 ]*)\]\]|",
            "NyansapowParser::renderPageLink",
            $line
        );
        
        return $line;
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
}
