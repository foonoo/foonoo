<?php


namespace foonoo\content;


/**
 * @author ekow
 */
interface ContentFactory
{
    public function create(string $source, string $destination): Content;
}