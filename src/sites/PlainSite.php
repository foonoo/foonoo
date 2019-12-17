<?php

namespace nyansapow\sites;

/**
 * 
 */
class PlainSite extends AbstractSite
{
    public function getPages() : array
    {
        $pages = array();

        $files = $this->getFiles();
        foreach ($files as $file) {
            $sourceFile = $this->getSourcePath($file);
            $destinationFile = $file;
            $pages []= $this->automaticContentFactory->create($this, $sourceFile, $destinationFile);
        }

        return $pages;
    }

    public function getDefaultTheme() : string
    {
        return 'plain';
    }

    public function getType() : string
    {
        return 'plain';
    }
}
