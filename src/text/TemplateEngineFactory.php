<?php


namespace nyansapow\text;


use ntentan\honam\EngineRegistry;
use ntentan\honam\engines\php\HelperVariable;
use ntentan\honam\engines\php\Janitor;
use ntentan\honam\factories\MustacheEngineFactory;
use ntentan\honam\factories\PhpEngineFactory;
use ntentan\honam\TemplateFileResolver;
use ntentan\honam\TemplateRenderer;
use nyansapow\sites\AbstractSite;

class TemplateEngineFactory
{
    private $engineRegistry;

    public function create($site)
    {
        $engineRegistry = new EngineRegistry();
        $fileResolver = new TemplateFileResolver();
        $renderer = new TemplateRenderer($engineRegistry, $fileResolver);
        $templateEngine = new TemplateEngine($fileResolver, $renderer);
        $engineRegistry->registerEngine(['mustache'], new MustacheEngineFactory($fileResolver));
        $engineRegistry->registerEngine(['tpl.php'],
            new PhpEngineFactory($renderer, new HelperVariable($renderer, $fileResolver), new Janitor())
        );
        $this->setupLocalTemplatePaths($site, $templateEngine);
        return $templateEngine;
    }

    /**
     * @param AbstractSite $site
     */
    private function setupLocalTemplatePaths($site, $templateEngine)
    {
        $path = $site->getSourcePath();
        $siteTemplates = $site->getSetting('templates');
        if (is_array($siteTemplates)) {
            foreach ($siteTemplates as $template) {
                $templateEngine->prependPath($path . $template);
            }
        } else if ($siteTemplates) {
            $templateEngine->prependPath($path . $siteTemplates);
        }

        if (is_dir("{$path}np_templates")) {
            $templateEngine->prependPath("{$path}np_templates");
        }
        $templateEngine->prependPath(__DIR__ . "/../../themes/parser");

    }
}
