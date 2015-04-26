<?php

namespace nyansapow;

use ntentan\honam\TemplateEngine;

class TextRenderer
{
    private static $titles;
    private static $info = null;
    
    private static function getInfo()
    {
        if(self::$info === null)
        {
            self::$info = finfo_open(FILEINFO_MIME);
        }
        return self::$info;
    }
    
    public static function render($content, $filename, $data = [])
    {
        $currentDocument = new \DOMDocument();
        $preParsed = Parser::preParse($content);
        $markedup = self::parse($preParsed, $filename, $data);        
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
    
    private static function parse($content, $filename, $data)
    {
        $format = pathinfo($filename, PATHINFO_EXTENSION);
        switch($format)
        {
            case 'md': 
                return self::parseMarkdown($content); 
                
            default: 
                if(TemplateEngine::canRender($filename))
                {
                    return TemplateEngine::renderString($content, $format, $data);
                }
                else
                {
                    return $content;
                }
        }
    }
    
    private static function parseMarkdown($content)
    {
        $parsedown = new \Parsedown();
        return $parsedown->text($content);
    }
   
    public static function isFileRenderable($file)
    {
        $mimeType = finfo_file(self::getInfo(), $file);
        return (substr($mimeType, 0, 4) === 'text' && substr($file, -2) == 'md') || 
            TemplateEngine::canRender($file);
    }
        
}
