<?php


namespace nyansapow\sites;


interface ContentFactoryInterface
{
    public function create(AbstractSite $site, string $source, string $destination): ContentInterface;
}