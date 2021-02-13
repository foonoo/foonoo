<?php

namespace foonoo\sites;

use foonoo\text\TemplateEngine;

/**
 * The defauls site generated when tere are no configurations in the root directory.
 *
 * A plain site reads in and converts any supported text formats (Markdown and Templates) to html. If there is an index
 * template file, or an index markdown that becomes the default page for the site. The plain site was added so a site
 * could easily be put together from a bunch of Markdown files. With the additional support of foonoo tags, links could
 * easily be created between these markdown files, and simples sites could be built without much effort.
 */
class PlainSite extends AbstractSite
{
    private $templateEngine;

    public function __construct(TemplateEngine $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * Converts the extensions of all renderable files to .html
     *
     * @param $file
     * @return string
     */
    private function convertExtensions(string $file): string
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if ($extension == 'md' || $this->templateEngine->isRenderable($file)) {
            return substr($file, 0, -strlen(".$extension")) . '.html';
        } else {
            return $file;
        }
    }

    /**
     * Return all the content needed to render the site.
     *
     * @return array
     */
    public function getContent(): array
    {
        $content = array();

        $files = $this->getFiles();
        foreach ($files as $file) {
            $sourceFile = $this->getSourcePath($file);
            $destinationFile = $this->convertExtensions($file);
            $content [] = $this->automaticContentFactory->create($sourceFile, $destinationFile);
        }

        return $content;
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
