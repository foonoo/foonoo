<?php


namespace nyansapow\content;


use nyansapow\sites\AbstractSite;
use nyansapow\content\ContentInterface;

interface ContentFactoryInterface
{
    public function create(AbstractSite $site, string $source, string $destination): ContentInterface;
}