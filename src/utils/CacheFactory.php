<?php


namespace foonoo\utils;

use ntentan\kaikai\backends\FileCache;

class CacheFactory
{
    public function create($path): Cache
    {
        return new Cache(new \ntentan\kaikai\Cache(new FileCache(['path' => $path])));
    }
}
