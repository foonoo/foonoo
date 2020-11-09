<?php

namespace foonoo\sites;

use foonoo\text\TemplateEngine;

/**
 *
 */
class PlainSite extends AbstractSite
{
    private $templateEngine;

    public function __construct(TemplateEngine $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    public function convertExtensions($file)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if ($extension == 'md' || $this->templateEngine->isRenderable($file)) {
            return substr($file, 0, -strlen(".$extension")) . '.html';
        } else {
            return $file;
        }
    }

    public function getPages(): array
    {
        $pages = array();

        $files = $this->getFiles();
        foreach ($files as $file) {
            $sourceFile = $this->getSourcePath($file);
            $destinationFile = $this->convertExtensions($file);
            $pages [] = $this->automaticContentFactory->create($sourceFile, $destinationFile);
        }

        return $pages;
    }

    public function getDefaultTheme(): string
    {
        return 'plain';
    }

    public function getType(): string
    {
        return 'plain';
    }
}
