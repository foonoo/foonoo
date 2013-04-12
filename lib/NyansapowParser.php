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
        $line = preg_replace_callback(
            "/\[\[(http:\/\/)(?<link>.*)\]\]/",
            "NyansapowParser::renderLink",
            $line
        );
        
        // Match images
        $line = preg_replace_callback(
            "/\[\[(?<image>.*\.(jpeg|jpg|png|gif))(\|'?(?<alt>[a-zA-Z0-9 ]*)'?)?(?<options>[a-zA-Z_=|:]+)?\]\]/",
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
                $caption = "<p>{$matches['alt']}</p>";
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
                return "<a href='{$page}.html'>{$matches['markup']}</a>";
            }
        }
    }
    
    public static function renderLink($matches)
    {
        return "<a href='http://{$matches['link']}'>http://{$matches['link']}</a>";
    }
}
