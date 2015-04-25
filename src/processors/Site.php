<?php
namespace nyansapow\processors;

use nyansapow\TextRenderer;

class Site extends \nyansapow\Processor
{   
    public function init()
    {
        $this->setTheme('default');
        $this->info = finfo_open(FILEINFO_MIME);
    }
    
    public function outputSite()
    {
        $files = $this->getFiles();
        foreach($files as $file)
        {
            if(TextRenderer::isFileRenderable($file))
            {
                $content = $this->readFile($file);
                $this->setOutputPath($this->adjustExtension($file));
                $markedup = TextRenderer::render($content['body'], $file);
                $this->outputPage($markedup);
            }
            else
            {
                copy($this->getSourcePath($file), $this->getDestinationPath($file));
            }
        }
    }
    
    private function adjustExtension($file)
    {
        $path = explode('.', $file);
        if(TextRenderer::isFileRenderable($file))
        {
            $path[count($path) - 1] = 'html';
        }
        return implode('.', $path);
    }
}