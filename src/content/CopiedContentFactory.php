<?php


namespace nyansapow\content;


use nyansapow\content\ContentFactoryInterface;
use nyansapow\content\ContentInterface;
use nyansapow\content\CopiedContent;
use nyansapow\sites\AbstractSite;

class CopiedContentFactory implements ContentFactoryInterface
{

    public function create(string $source, string $destination): ContentInterface
    {
        return new CopiedContent($source, $destination);
    }
}