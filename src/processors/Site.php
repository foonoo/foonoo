<?php
namespace nyansapow\processors;

class Site extends \nyansapow\Processor
{
    public function init()
    {
        $this->setTheme('default');
    }
    
    public function outputSite()
    {
        $files = $this->getFiles();
        $info = finfo_open(FILEINFO_MIME);
        foreach($files as $file)
        {
            $mimeType = finfo_file($info, $file);
            if(substr($mimeType, 0, 4) === 'text' && substr($file, -2) == 'md')
            {
                $content = $this->readFile($file);
                $this->setOutputPath($this->adjustExtension($file));
                $markedup = \nyansapow\TextRenderer::render($file, $content['body']);
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
        if(end($path) == 'md' || end($path) == 'textile')
        {
            $path[count($path) - 1] = 'html';
        }
        return implode('.', $path);
    }
}