<?php


namespace nyansapow\content;


interface ContentFactory
{
    public function create(string $source, string $destination): Content;
}