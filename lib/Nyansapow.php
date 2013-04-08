<?php

require "php-markdown/Michelf/Markdown.php";
require "php-markdown/Michelf/MarkdownExtra.php";
require "mustache/src/Mustache/Autoloader.php";

Mustache_Autoloader::register();

class Nyansapow
{
    private $options;
    private $source;
    public static $pages = array();
    private $pageFiles = array();
    
    private function __construct($source, $options)
    {
        if($source == '')
        {
            throw new NyansapowException("Please specify where your wiki source files are located.");
        }
        
        if(!file_exists($source) && !is_dir($source)) 
        {
            throw new NyansapowException("Input directory `{$source}` does not exist or is not a directory.");
        }
        
        $dir = dir($source);
        while (false !== ($entry = $dir->read())) 
        {
            if(!preg_match("/(?<page>.*)(\.)(?<extension>\md|\textile)/i", $entry, $matches))
            {
                continue;
            }            
            self::$pages[] = $matches['page'];
            $this->pageFiles[] = $entry;
        }        
        
        $this->source = $source;
        $this->options = $options;
    }

    public static function open($source, $options = array())
    {
        return new Nyansapow($source, $options);
    }
    
    public function write($destination, $files = array())
    {
        $home = dirname(__DIR__);
        
        if($destination == '')
        {
            $destination = getcwd();
        }

        if(!file_exists($destination) && !is_dir($destination))
        {
            throw new NyansapowException("Output directory `{$destination}` does not exist or is not a directory.");
        }
        
        if(count($files) == 0)
        {
            // Copy assets from the theme
            self::copyDir("$home/themes/default/assets", "{$destination}");
            $files = $this->pageFiles;
        }
        
        foreach($files as $file)
        {
            if(!preg_match("/(?<page>.*)(\.)(?<extension>\md|\textile)/i", $file, $matches))
            {
                continue;
            }
            
            switch($matches['page'])
            {
                case 'Home':
                    $output = "index.html";
                    break;

                default:
                    $output = "{$matches['page']}.html";
                    break;
            }

            $outputFile = "{$destination}/~$output";
            $inputFile = "{$this->source}/$file";
            
            $m = new Mustache_Engine();   
            $layout = file_get_contents("$home/themes/default/templates/layout.mustache");
            $content = \Michelf\MarkdownExtra::defaultTransform(file_get_contents($inputFile));
            
            $document = new DOMDocument();
            @$document->loadHTML($content);
            $h1s = $document->getElementsByTagName('h1');
            
            $webPage = $m->render(
                $layout, 
                array(
                    'body' => $content,
                    'title' => $h1s->item(0)->nodeValue,
                    'name' => $this->options['name'],
                    'date' => date('jS F, Y H:i:s')
                )
            );

            file_put_contents($outputFile, $webPage);
            $filesWritten[] = $output;
        }        

        foreach($filesWritten as $fileWritten)
        {
            $inputFile = fopen("{$destination}/~$fileWritten", 'r');
            $outputFile = fopen("{$destination}/$fileWritten", 'w');
            while(!feof($inputFile))
            {
                fputs($outputFile, Nyansapow::parse(fgets($inputFile)));
            }
            fclose($inputFile);
            fclose($outputFile);
            unlink("{$destination}/~$fileWritten");
        }        
    }
    
    private static function parseLink($matches)
    {
        
    }
    
    private function parse($line)
    {
        return preg_replace_callback(
            "|\[\[(?<markup>.*)\]\]|",
            create_function(
                '$matches',
                '$link = str_replace(array(\' \', \'/\'), \'-\', $matches[\'markup\']);
                foreach(Nyansapow::$pages as $page)
                {
                    if(strtolower($page) == strtolower($link))
                    {
                        $link = $page;
                        break;
                    }
                }
                return "<a href=\'{$link}.html\'>{$matches[\'markup\']}</a>";'
            ),
            $line
        );
    }

    public static function copyDir($source, $destination)
    {
        foreach(glob($source) as $file)
        {
            $newFile = (is_dir($destination) ?  "$destination/" : ''). basename("$file");

            if(is_dir($file))
            {
                self::mkdir($newFile);
                self::copyDir("$file/*", $newFile);
            }
            else
            {
                copy($file, $newFile);
            }
        }
    }

    private static function mkdir($path)
    {
        if(!\is_writable(dirname($path)))
        {
            throw new NyansapowException("You do not have permissions to create the $path directory.");
        }
        else if(\is_dir($path))
        {
            // Skip
        }
        else
        {
            mkdir($path);
        }
        return $path;
    }    
}

class NyansapowException extends Exception
{
    
}

