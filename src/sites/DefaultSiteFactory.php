<?php


namespace foonoo\sites;


use foonoo\content\AutomaticContentFactory;

class DefaultSiteFactory implements SiteFactoryInterface
{
    private $automaticContentFactory;
    private $assetPipeline;

    public function __construct(AutomaticContentFactory $automaticContentFactory, AssetPipeline $assetPipeline)
    {
        $this->automaticContentFactory = $automaticContentFactory;
        $this->assetPipeline = $assetPipeline;
    }

    public function create(array $metadata, string $path): AbstractSite
    {
        $class = "\\nyansapow\\sites\\" . ucfirst($metaData['type'] ?? 'plain') . "Site";

        /** @var AbstractSite $instance */
        $instance = (new \ReflectionClass($class))->newInstance();
        $instance->setAutomaticContentFactory($this->automaticContentFactory);
        $instance->setAssetPipeline($this->assetPipeline);
        return $instance;
    }
}
