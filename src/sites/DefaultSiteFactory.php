<?php


namespace foonoo\sites;


use foonoo\asset_pipeline\AssetPipeline;
use foonoo\content\AutomaticContentFactory;
use foonoo\text\TemplateEngine;
use foonoo\text\TocGenerator;

class DefaultSiteFactory implements SiteFactoryInterface
{
    private $automaticContentFactory;
    private $assetPipeline;
    private $templateEngine;
    private $tocGenerator;

    public function __construct(AutomaticContentFactory $automaticContentFactory, AssetPipeline $assetPipeline, TemplateEngine $templateEngine, TocGenerator $tocGenerator)
    {
        $this->automaticContentFactory = $automaticContentFactory;
        $this->assetPipeline = $assetPipeline;
        $this->templateEngine = $templateEngine;
        $this->tocGenerator = $tocGenerator;
    }

    public function create(array $metadata, string $path): AbstractSite
    {
        $instance = new DefaultSite($this->templateEngine, $this->tocGenerator);
        $instance->setAutomaticContentFactory($this->automaticContentFactory);
        $instance->setAssetPipeline($this->assetPipeline);
        return $instance;
    }
}
