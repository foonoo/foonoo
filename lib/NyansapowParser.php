<?php
class NyansapowParser
{
    private static $nyansapow;
    
    public static function setNyansapow($nyansapow)
    {
        self::$nyansapow = $nyansapow;
    }
    
    public static function parse($line)
    { 
        // Match images
        $line = preg_replace_callback(
            "/\[\[(?<image>.*\.(jpeg|jpg|png|gif))(?:\|(?<alt>[a-zA-Z0-9 ]*))+\]\]/",
            "NyansapowParser::renderImageTag",
            $line
        );
        
        // Match page links
        $line =  preg_replace_callback(
            "|\[\[(?<markup>.*)\]\]|",
            "NyansapowParser::renderPageLink",
            $line
        );        
        
        return $line;
    }
    
    public static function renderImageTag($matches)
    {
        return "<img src='images/{$matches['image']}' alt='{$matches['alt']}' />";
    }
    
    public static function renderPageLink($matches)
    {
        $link = str_replace(array(' ', '/'), '-', $matches['markup']);
        foreach(self::$nyansapow->getPages() as $page)
        {
            if(strtolower($page) == strtolower($link))
            {
                return "<a href='{$page}.html'>{$matches['markup']}</a>";
            }
        }
    }
}
