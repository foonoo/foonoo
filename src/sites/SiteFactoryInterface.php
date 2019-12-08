<?php


namespace nyansapow\sites;


interface SiteFactoryInterface
{
    public function create(array $metadata, string $path) : AbstractSite;
}