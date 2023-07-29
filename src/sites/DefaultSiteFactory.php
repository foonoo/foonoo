<?php
namespace foonoo\sites;

use foonoo\content\AutomaticContentFactory;
use foonoo\text\TemplateEngine;
use foonoo\text\TocGenerator;
use foonoo\text\TextConverter;

class DefaultSiteFactory implements SiteFactoryInterface
{
    private $automaticContentFactory;
    private $templateEngine;
    private $tocGenerator;
    private $textConverter;

    public function __construct(AutomaticContentFactory $automaticContentFactory, TemplateEngine $templateEngine, TocGenerator $tocGenerator, TextConverter $textConverter)
    {
        $this->automaticContentFactory = $automaticContentFactory;
        $this->templateEngine = $templateEngine;
        $this->tocGenerator = $tocGenerator;
        $this->textConverter = $textConverter;
    }

    public function create(): AbstractSite 
    {
        $instance = new DefaultSite($this->templateEngine, $this->tocGenerator, $this->textConverter);
        $instance->setAutomaticContentFactory($this->automaticContentFactory);
        return $instance;
    }
}
