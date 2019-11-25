<?php

namespace nyansapow\sites;

use ntentan\honam\TemplateRenderer;
use nyansapow\text\HtmlRenderer;

class ContentFactory
{
    private $templateRenderer;
    private $htmlRenderer;

    public function __construct(HtmlRenderer $htmlRenderer, TemplateRenderer $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
        $this->htmlRenderer = $htmlRenderer;
    }

    /**
     * Create a new Content object
     *
     * @param $source
     * @param $destination
     * @param array $data
     * @return ContentInterface
     */
    public function create($source, $destination, $data=[]) : ContentInterface
    {
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        if (isset($data['blog'])) {
            return new BlogContent($this->htmlRenderer, $source, $destination, $data);
        } else if (file_exists($source) && !in_array($extension, ['mustache', 'php'])) {
            return new CopiedContent($source, $destination);
        } else if(!empty($data)) {
            return new TemplateContent($this->templateRenderer, $source, $destination, $data);
        } else if ($extension == 'md') {
            return new MarkupContent($this->htmlRenderer, $source, $destination);
        }
    }
}
