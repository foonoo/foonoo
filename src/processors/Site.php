<?php

namespace nyansapow\processors;

use nyansapow\TextRenderer;

/**
 * 
 */
class Site extends AbstractProcessor
{
    public function outputSite()
    {
        $files = $this->getFiles();
        foreach ($files as $file) {
            $sourceFile = $this->getSourcePath($file);
            if (TextRenderer::isFileRenderable($sourceFile)) {
                $content = $this->readFile($file);
                $this->setOutputPath($this->adjustExtension($file));
                $markedup = TextRenderer::render($content['body'], pathinfo($file, PATHINFO_EXTENSION), ['data' => $this->data]);
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
