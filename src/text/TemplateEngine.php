<?php
namespace nyansapow\text;

use ntentan\honam\TemplateFileResolver;
use ntentan\honam\TemplateRenderer;

class TemplateEngine
{
    private $templateRenderer;
    private $templateFileResolver;

    public function __construct(TemplateFileResolver $templateFileResolver, TemplateRenderer $templateRenderer)
    {
        $this->templateFileResolver = $templateFileResolver;
        $this->templateRenderer = $templateRenderer;
    }

    public function prependPath(string $path)
    {
        $this->templateFileResolver->prependToPathHierarchy($path);
    }

    public function render(string $template, array $data, array $hierarchy = [])
    {
        if(!empty($hierarchy)) {
            $this->templateFileResolver->setPathHierarchy($hierarchy);
        }
        return $this->templateRenderer->render($template, $data);
    }
}
