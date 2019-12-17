<?php


namespace nyansapow\sites;


class CopiedContentFactory implements ContentFactoryInterface
{

    public function create(AbstractSite $site, string $source, string $destination): ContentInterface
    {
        return new CopiedContent($source, $destination);
    }
}