<?php

namespace nyansapow\sites;

use ntentan\utils\exceptions\FileAlreadyExistsException;
use ntentan\utils\exceptions\FileNotWriteableException;
use nyansapow\NyansapowException;

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
            $sourceFile = $this->getPathInSource($file);
            $destinationFile = $this->getPathInDestination($file);
            $pages []= $this->pageFactory->create($sourceFile, $destinationFile);
        }

        return $pages;
    }

    protected function getDefaultTheme() {
        return 'plain';
    }

    public function getType() : string
    {
        return 'plain';
    }
}
