<?php


namespace foonoo\utils;


class Cache
{
    private $cache;

    public function __construct(\ntentan\kaikai\Cache $cache)
    {
        $this->cache = $cache;
    }

    private function wrap($content)
    {
        return ['time' => time(), 'content' => $content];
    }

    public function get(string $key, callable $factory, $time = null)
    {
        if($this->cache->exists($key)) {
            $value = $this->cache->read($key);
            if($time && $time > $value['time']) {
                $value = $this->wrap($factory());
                $this->cache->write($key, $value, 16588800);
            }
        } else {
            $value = $this->wrap($factory());
            $this->cache->write($key, $value, 16588800);
        }
        return $value['content'];
    }
}
