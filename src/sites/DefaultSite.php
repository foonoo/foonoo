<?php

namespace foonoo\sites;

use foonoo\text\TemplateEngine;
use foonoo\text\TocGenerator;
use foonoo\content\TocContent;
use foonoo\text\TextConverter;
use foonoo\content\IndexWrapper;

/**
 * The default site generated when there are either no configurations in the root directory or a site type is not
 * specified in the configuration.
 *
 * Default sites read in and converts any supported text formats (Markdown and Templates) to html. If there is an index
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
     * Generates the table of contents for a site.
     * 
     * @var TocGenerator
     */
    private $tocGenerator;
    
    /**
     * Converts texts between different formats.
     * 
     * @var TextConnverter
     */
    private $textConverter;

    public function __construct(TemplateEngine $templateEngine, TocGenerator $tocGenerator, TextConverter $textConverter)
    {
        $this->templateEngine = $templateEngine;
        $this->tocGenerator = $tocGenerator;
        $this->textConverter = $textConverter;
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
        if ($this->textConverter->isConvertible($extension, 'html') || $this->templateEngine->isRenderable($file)) {
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
        
        $this->addIndexContent($content);
        return $content;
    }
    
    private function addIndexContent(array &$content)
    {
        $index = $this->getMetaData()['index'] ?? null;
        if ($index == "_TOC_") {
            $content[] = new TocContent($this->tocGenerator, $this->templateEngine);
        } else if ($index !== null) {
            foreach($content as $c) {
                if($c->getMetaData()['frontmatter']['title'] ?? '' == $index) {
                    $content[] = new IndexWrapper($c);
                    break;
                }
            }
        }
        return $content;
    }

    /**
     * @return string
     */
    public function getDefaultTheme(): string
    {
        return 'site';
    }

    /**
     * 
     * @param string $contentDestination
     * @return array
     */
    public function getTemplateData(string $contentDestination = null): array
    {
        $templateData = parent::getTemplateData($contentDestination);
        if(isset($this->metaData['enable-toc']) && $this->metaData['enable-toc'] == true) {
            $globalToc = $this->tocGenerator->getGlobalTOC();
            $templateData['has_toc'] = true;
            $templateData['global_toc'] = $globalToc;            
        }
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

    public function initialize(string $path, array $metadata): void {
        // Do nothing
    }

}
