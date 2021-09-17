<?php

namespace foonoo\content;

use foonoo\text\TocGenerator;
use foonoo\text\TemplateEngine;

/**
 * Description of TOCContent
 *
 * @author ekow
 */
class TocContent extends Content
{
    /**
     * 
     * @var TocGenerator
     */
    private $tocGenerator;
    
    /**
     * 
     * @var TemplateEngine
     */
    private $templateEngine;
    
    public function __construct(TocGenerator $tocGenerator, TemplateEngine $templateEngine)
    {
        $this->tocGenerator = $tocGenerator;
        $this->templateEngine = $templateEngine;
        $this->destination = "index.html";
    }
    
    public function getMetaData(): array
    {
        return [];
    }

    public function render(): string
    {
        //return "TOC";
        return $this->templateEngine->render("table_of_contents_tag", $this->tocGenerator->getGlobalTOC());
    }
}
