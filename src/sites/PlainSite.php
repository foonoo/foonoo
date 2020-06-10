<?php

namespace foonoo\sites;

/**
 * 
 */
class PlainSite extends AbstractSite
{
    public function convertExtensions($file)
    {
        if(pathinfo($file, PATHINFO_EXTENSION) == 'md') {
            return substr($file, 0, -strlen('.md')) . '.html';
        } else {
            return $file;
        }
    }

    public function getPages() : array
    {
        $pages = array();

        $files = $this->getFiles();
        foreach ($files as $file) {
            $sourceFile = $this->getSourcePath($file);
            $destinationFile = $this->convertExtensions($file);
            $pages []= $this->automaticContentFactory->create($sourceFile, $destinationFile);
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
