<?php


namespace foonoo\sites;


use foonoo\asset_pipeline\AssetPipelineFactory;
use foonoo\content\AutomaticContentFactory;
use foonoo\text\TemplateEngine;
use foonoo\text\TocGenerator;
use foonoo\text\TextConverter;

class DefaultSiteFactory implements SiteFactoryInterface
{
    private $automaticContentFactory;
    private $assetPipelineFactory;
    private $templateEngine;
    private $tocGenerator;
    private $textConverter;

    public function __construct(AutomaticContentFactory $automaticContentFactory, AssetPipelineFactory $assetPipeline, TemplateEngine $templateEngine, TocGenerator $tocGenerator, TextConverter $textConverter)
    {
        $this->automaticContentFactory = $automaticContentFactory;
        $this->assetPipelineFactory = $assetPipeline;
        $this->templateEngine = $templateEngine;
        $this->tocGenerator = $tocGenerator;
        $this->textConverter = $textConverter;
    }

    public function create(): AbstractSite 
    {
        $instance = new DefaultSite($this->templateEngine, $this->tocGenerator, $this->textConverter);
        $instance->setAutomaticContentFactory($this->automaticContentFactory);
        $instance->setAssetPipeline($this->assetPipelineFactory->create());
        return $instance;
    }
}
