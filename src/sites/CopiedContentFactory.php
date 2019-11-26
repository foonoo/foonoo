<?php


namespace nyansapow\sites;


class CopiedContentFactory implements ContentFactoryInterface
{

    public function create($source, $destination, $data): ContentInterface
    {
        return new CopiedContent($source, $destination);
    }
}