<?php

namespace nyansapow;

use ntentan\honam\TemplateEngine;

class TextRenderer
{
    private static $title;
    private static $info = null;

    private static function getInfo()
    {
        if(self::$info === null)
        {
            self::$info = finfo_open(FILEINFO_MIME);
        }
        return self::$info;
    }

    public static function render($content, $filename, $options = [])
    {
        $currentDocument = new \DOMDocument();
        $preParsed = Parser::preParse($content);
        $markedup = self::parse($preParsed, $filename, is_array($options['data']) ? $options['data'] : [] );
        @$currentDocument->loadHTML($markedup);
        self::$title = $currentDocument->getElementsByTagName('h1')->item(0)->textContent;
        Parser::domCreated($currentDocument);
        $body = $currentDocument->getElementsByTagName('body');

        try
        {
            // Force the parsing of a TOC
            if($options['toc'])
            {
                throw new TocRequestedException();
            }
            $parsed = Parser::postParse($markedup);
        }
        catch (TocRequestedException $e)
        {
            $parsed = Parser::postParse(
                str_replace(
                    array('<body>', '</body>'),
                    '', $currentDocument->saveHTML($body->item(0))
                ),
                false
            );
        }

        return $parsed;
    }

    public static function getTitle()
    {
        return self::$title;
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
        $parsedown = new \ParsedownExtra();
        return $parsedown->text($content);
    }

    public static function isFileRenderable($file)
    {
        $mimeType = finfo_file(self::getInfo(), $file);
        return (substr($mimeType, 0, 4) === 'text' && substr($file, -2) == 'md') ||
            TemplateEngine::canRender($file);
    }

    public static function getTableOfContents()
    {
        return Parser::getTableOfContents();
    }

    public static function setTypeIndex($typeIndex)
    {
        Parser::setTypeIndex($typeIndex);
    }

    public static function setPages($pages)
    {
        Parser::setPages($pages);
    }
}
