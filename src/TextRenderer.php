<?php

namespace nyansapow;

class TextRenderer
{
    private static $titles;
    
    public static function render($file, $input = false)
    {
        $currentDocument = new \DOMDocument();  
        if($input === false)
        {
            $input = file_get_contents($file);
        }
        
        $preParsed = Parser::preParse($input);
        $markedup = self::parse($file, $preParsed);
        @$currentDocument->loadHTML($markedup);
        self::$titles = $currentDocument->getElementsByTagName('h1');
        Parser::domCreated($currentDocument);
        $body = $currentDocument->getElementsByTagName('body');
        
        return Parser::postParse(
            str_replace(array('<body>', '</body>'), '', $currentDocument->saveHTML($body->item(0)))
        );        
    }
    
    public static function getTitles()
    {
        return $this->titles;
    }
    
    private static function parse($file, $content)
    {
        @$format = end(explode('.', $file));
        switch($format)
        {
            case 'md': return self::parseMarkdown($content);
            default: return $content;
        }
    }
    
    private static function parseMarkdown($content)
    {
        $parsedown = new \Parsedown();
        return $parsedown->text($content);
    }
}
