<?php
namespace nyansapow\processors;

use nyansapow\TextRenderer;

/**
 * 
 */
class Wiki extends \nyansapow\Processor
{
    private $pages = array();
    private $toc = array();
    
    public function init()
    {
        $this->setTheme('wiki');
    }
    
    private function getPages()
    {
        return $this->pages;
    }
    
    private function getPageOutput($page)
    {
        switch($page)
        {
            case 'Home':
                $output = "index.html";
                break;

            default:
                $output = "{$page}.html";
                break;
        }        
        return $output;
    }


    private function addPage($path)
    {
        $file = basename($path);
        if(preg_match("/(?<page>.*)(\.)(?<extension>.*)/i", $file, $matches))
        {
            $content = $this->readFile($file);
            $output = $this->getPageOutput($matches['page']);
            $page = array(
                'path' => $path,
                'page' => $matches['page'],
                'extension' => $matches['extension'],
                'content' => $content,
                'output' => $output,
                'markedup' => TextRenderer::render($content['body'], $file),
                'title' => isset($content['fontmatter']['title']) ? 
                    $content['frontmatter']['title'] : TextRenderer::getTitle()
            );
            $this->toc[] = array(
                'title' => $page['title'],
                'url' => $output,
                'children' => TextRenderer::getTableOfContents()
            );
            $this->pages[] = $page;
        }        
    }
    
    public function outputSite() 
    {
        $files = $this->getFiles();
        foreach($files as $path)
        {
            $fullPath = $this->getSourcePath($path);
            if(TextRenderer::isFileRenderable($fullPath))
            {
                $this->addPage($fullPath);
            }
        }
        
        foreach($this->getPages() as $page)
        {   
            $this->setOutputPath($page['output']);
            $this->outputPage(
                $page['markedup'],
                array(
                    'output' => $page['output'],
                    'title' => $page['title'],
                    'date' => date('jS F, Y H:i:s'),
                    'toc' => $this->toc
                )
            );
        }
    }
}
