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
     * An instance of the table of contents generator.
     */
    private TocGenerator $tocGenerator;
    
    /**
     * An instance of the template engine.
     */
    private TemplateEngine $templateEngine;
    
    /**
     * @param TocGenerator $tocGenerator
     * @param TemplateEngine $templateEngine
     */
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
        return $this->templateEngine->render("toc", ['tree' => $this->tocGenerator->getGlobalTOC()]);
    }

    public function getID(): string {
        return uniqid("toc_");
    }
}
