<?php


namespace foonoo\content;

use foonoo\sites\FrontMatterReader;
use foonoo\text\TemplateEngine;
use foonoo\text\TextConverter;

class BlogPageContent extends MarkupContent implements ThemableInterface
{
    protected $template = "page";
    private $templateEngine;
    private $rendered;

    public function __construct(TemplateEngine $templateEngine, TextConverter $textConverter, FrontMatterReader $frontMatterReader, string $document, string $destination)
    {
        parent::__construct($textConverter, $frontMatterReader, $document, $destination);
        $this->templateEngine = $templateEngine;
    }

    public function getLayoutData()
    {
        return ['page_type' => 'page'];
    }

    public function render(): string
    {
        if(!$this->rendered) {
            $this->rendered = $this->templateEngine->render($this->template, ['body' => parent::render()] + $this->getMetaData());
        }
        return $this->rendered;
    }
}
