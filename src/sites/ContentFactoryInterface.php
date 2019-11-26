<?php


namespace nyansapow\sites;


interface ContentFactoryInterface
{
    public function create($source, $destination, $data): ContentInterface;
}