<?php


namespace nyansapow;


use ntentan\kaikai\backends\FileCache;
use nyansapow\utils\Cache;

class CacheFactory
{
    public function create($path) : Cache
    {
        return new Cache(new \ntentan\kaikai\Cache(new FileCache(['path' => $path])));
    }
}
