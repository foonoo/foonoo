<?php


namespace foonoo\sites;


use foonoo\FoonooException;

class SiteTypeRegistry
{
    private $factories = [];

    public function register(SiteFactoryInterface $siteFactory, string $type)
    {
        $this->factories[$type] = $siteFactory;
    }

    public function get($type) : SiteFactoryInterface
    {
        if(!isset($this->factories[$type])) {
            throw new FoonooException("Could not create site of type '$type'");
        }
        return $this->factories[$type];
    }
}
