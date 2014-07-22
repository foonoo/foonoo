<?php
namespace nyansapow\processors;

use nyansapow\Parser;
use nyansapow\Callbacks;

class Wiki extends \nyansapow\SiteProcessor
{
    private $pages = array();
    
    public function getPages()
    {
        return $this->pages;
    }
    
    public function outputSite() 
    {
        $currentDocument = new \DOMDocument();
        
        foreach($this->getFiles() as $path)
        {
            $file = basename($path);
            if(preg_match("/(?<page>.*)(\.)(?<extension>\md|\textile)/i", $file, $matches))
            {
                $this->pages[]= array(
                    'path' => $path,
                    'page' => $matches['page'],
                    'extension' => $matches['extension'],
                    'file' => $file
                );
            }
        }
        
        foreach($this->getPages() as $page)
        {
            $file = $page['file'];
            $dir = dirname($page['path']);
            $assetsLocation = \nyansapow\SiteProcessor::getAssetsLocation($dir);
            
            \nyansapow\Nyansapow::mkdir(self::$nyansapow->getDestination() . '/' . $dir);
            
            switch($page['page'])
            {
                case 'Home':
                    $output = "index.html";
                    break;

                default:
                    $output = "{$page['page']}.html";
                    break;
            }                
            
            $input = file_get_contents(self::$nyansapow->getSource() . '/' . $page['path']);
            $outputFile = ($dir =='' ? '' : "/$dir") . "/$output";
                        
            $preParsed = Parser::preParse($input);
            
            \Michelf\MarkdownExtra::setCallbacks(new Callbacks());
            $markedup = \Michelf\MarkdownExtra::defaultTransform($preParsed);
            
            @$currentDocument->loadHTML($markedup);
            $h1s = $currentDocument->getElementsByTagName('h1');
            
            Parser::setProcessor($this);
            Parser::domCreated($currentDocument);
            
            $body = $currentDocument->getElementsByTagName('body');
            $content = Parser::postParse(
                str_replace(array('<body>', '</body>'), '', $currentDocument->saveHTML($body->item(0)))
            );
            
            $this->outputPage(
                $outputFile,
                array(
                    'body' => $content,
                    'page_title' => $h1s->item(0)->nodeValue,
                    'site_name' => $this->settings['site-name'],
                    'date' => date('jS F, Y H:i:s'),
                    'assets_location' => $assetsLocation                    
                )
            );
        }
    }
}
