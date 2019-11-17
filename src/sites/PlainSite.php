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
            $sourceFile = $this->getSourceRoot() . $file;
            $destinationFile = $this->getDestinationRoot() . $file;
            $pages []= $this->pageFactory->create($sourceFile, $destinationFile);
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
