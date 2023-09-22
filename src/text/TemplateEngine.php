<?php
namespace foonoo\text;

use ntentan\honam\TemplateFileResolver;
use ntentan\honam\TemplateRenderer;

/**
 * A small wrapper around honam's template engine.
 */
class TemplateEngine
{
    private TemplateRenderer $templateRenderer;
    private TemplateFileResolver $templateFileResolver;

    public function __construct(TemplateFileResolver $templateFileResolver, TemplateRenderer $templateRenderer)
    {
        $this->templateFileResolver = $templateFileResolver;
        $this->templateRenderer = $templateRenderer;
    }

    public function prependPath(string $path)
    {
        $this->templateFileResolver->prependToPathHierarchy($path);
    }

    public function setPathHierarchy($pathHierarchy)
    {
        $this->templateFileResolver->setPathHierarchy($pathHierarchy);
    }

    public function render(string $template, array $data)
    {
        return $this->templateRenderer->render($template, $data);
    }

    public function isRenderable($file) : bool
    {
        return $this->templateRenderer->canRender($file);
    }
}
