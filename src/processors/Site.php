<?php

namespace nyansapow\processors;

use nyansapow\TextRenderer;

/**
 * 
 */
class Site extends AbstractProcessor
{
    public function init()
    {
        $this->setTheme('site');
    }

    public function outputSite()
    {
        $files = $this->getFiles();
        foreach ($files as $file) {
            $sourceFile = $this->getSourcePath($file);
            if (TextRenderer::isFileRenderable($sourceFile)) {
                $content = $this->readFile($file);
                $this->setOutputPath($this->adjustExtension($file));
                $markedup = TextRenderer::render($content['body'], $file, ['data' => $this->data]);
                $this->outputPage($markedup);
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
}