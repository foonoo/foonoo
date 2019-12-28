<?php


namespace nyansapow\sites;


use nyansapow\content\AutomaticContentFactory;

class DefaultSiteFactory implements SiteFactoryInterface
{
    private $automaticContentFactory;

    public function __construct(AutomaticContentFactory $automaticContentFactory)
    {
        $this->automaticContentFactory = $automaticContentFactory;
    }

    public function create(array $metadata, string $path): AbstractSite
    {
        $class = "\\nyansapow\\sites\\" . ucfirst($metaData['type'] ?? 'plain') . "Site";
        /** @var AbstractSite $instance */
        $instance = (new \ReflectionClass($class))->newInstance();
        $instance->setAutomaticContentFactory($this->automaticContentFactory);

        return $instance;
    }
}
