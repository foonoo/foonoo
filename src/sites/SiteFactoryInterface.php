<?php


namespace foonoo\sites;


interface SiteFactoryInterface
{
    public function create(array $metadata, string $path) : AbstractSite;
}