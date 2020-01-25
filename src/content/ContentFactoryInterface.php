<?php


namespace nyansapow\content;


interface ContentFactoryInterface
{
    public function create(string $source, string $destination): ContentInterface;
}