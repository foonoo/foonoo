<?php

namespace nyansapow\generators;

/**
 * 
 */
class SiteGenerator extends AbstractGenerator
{
    public function outputSite()
    {
        $files = $this->getFiles();
        foreach ($files as $file) {
            $sourceFile = $this->getSourcePath($file);
            if ($this->textProcessors->isFileRenderable($sourceFile)) {
                $content = $this->readFile($file);
                $this->setOutputPath($this->adjustExtension($file));
                $markedup = $this->textProcessors->renderHtml($content['body'], pathinfo($file, PATHINFO_EXTENSION), ['data' => $this->data]);
                $this->writeContentToOutputPath($markedup);
            } else {
                copy($this->getSourcePath($file), $this->getDestinationPath($file));
            }
        }
    }

    private function adjustExtension($file)
    {
        $path = explode('.', $file);
        $path[count($path) - 1] = 'html';
        return implode('.', $path);
    }

    protected function getDefaultTheme() {
        return 'site';
    }
}