<?php


namespace foonoo\sites;


use foonoo\asset_pipeline\AssetPipeline;
use foonoo\content\AutomaticContentFactory;
use foonoo\text\TemplateEngine;

class DefaultSiteFactory implements SiteFactoryInterface
{
    private $automaticContentFactory;
    private $assetPipeline;
    private $templateEngine;

    public function __construct(AutomaticContentFactory $automaticContentFactory, AssetPipeline $assetPipeline, TemplateEngine $templateEngine)
    {
        $this->automaticContentFactory = $automaticContentFactory;
        $this->assetPipeline = $assetPipeline;
        $this->templateEngine = $templateEngine;
    }

    public function create(array $metadata, string $path): AbstractSite
    {
        $class = "\\foonoo\\sites\\" . ucfirst($metaData['type'] ?? 'default') . "Site";

        /** @var AbstractSite $instance */
        $instance = (new \ReflectionClass($class))->newInstanceArgs([$this->templateEngine]);
        $instance->setAutomaticContentFactory($this->automaticContentFactory);
        $instance->setAssetPipeline($this->assetPipeline);
        return $instance;
    }
}
