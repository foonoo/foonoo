<?php

namespace foonoo\sites;

use foonoo\events\AllContentsRendered;
use foonoo\events\EventDispatcher;
use foonoo\text\TemplateEngine;
use foonoo\text\TocGenerator;

/**
 * The default site generated when there are either no configurations in the root directory or a site type is not
 * specified in the configuration.
 *
 * A plain site reads in and converts any supported text formats (Markdown and Templates) to html. If there is an index
 * template file, or an index markdown that becomes the default page for the site. The plain site was added so a site
 * could easily be put together from a bunch of Markdown files. With the additional support of foonoo tags, links could
 * easily be created between these markdown files, and simples sites could be built without much effort.
 */
class DefaultSite extends AbstractSite
{
    /**
     * Instance of the template engine for rendering templated pages.
     *
     * @var TemplateEngine
     */
    private $templateEngine;

    /**
     * @var TocGenerator
     */
    private $tocGenerator;

    private $globalTOC;

    public function __construct(TemplateEngine $templateEngine, TocGenerator $tocGenerator)
    {
        $this->templateEngine = $templateEngine;
        $this->tocGenerator = $tocGenerator;
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
            $content[] = $this->automaticContentFactory->create($sourceFile, $destinationFile);
        }

        return $content;
    }

    /**
     * @return string
     */
    public function getDefaultTheme(): string
    {
        return 'default';
    }

    public function getTemplateData(string $contentDestination = null): array
    {
        $templateData = parent::getTemplateData($contentDestination);
        $globalToc = $this->tocGenerator->getGlobalTOC();
        ksort($globalToc);
        $templateData['global_toc'] = $globalToc;
        if(isset($this->metaData['title'])) {
            $templateData['site_title'] = $this->metaData['title'];
        }
        return $templateData;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'default';
    }
}
