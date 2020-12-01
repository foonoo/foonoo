<?php


namespace foonoo\content;


interface ContentFactory
{
    public function create(string $source, string $destination): Content;
}