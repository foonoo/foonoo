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
    
    private function addPage($path)
    {
        $file = basename($path);
        if(preg_match("/(?<page>.*)(\.)(?<extension>.*)/i", $file, $matches))
        {
            $content = $this->readFile($file);
            $page = array(
                'path' => $path,
                'page' => $matches['page'],
                'extension' => $matches['extension'],
                'content' => $content,
                'markedup' => TextRenderer::render($content['body'], $file),
                'title' => isset($content['fontmatter']['title']) ? 
                    $content['frontmatter']['title'] : TextRenderer::getTitle()
            );
            $this->toc[] = array(
                'title' => $page['title'],
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
            switch($page['page'])
            {
                case 'Home':
                    $output = "index.html";
                    break;

                default:
                    $output = "{$page['page']}.html";
                    break;
            }
            
            $this->setOutputPath($output);
            $this->outputPage(
                $page['markedup'],
                array(
                    'title' => $page['title'],
                    'date' => date('jS F, Y H:i:s'),
                    'toc' => $this->toc
                )
            );
        }
    }
}
