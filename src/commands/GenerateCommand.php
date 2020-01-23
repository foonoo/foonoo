<?php

namespace nyansapow\commands;

use nyansapow\CacheFactory;
use nyansapow\CommandInterface;
use nyansapow\Builder;

/**
 * Description of GenerateCommand
 *
 * @author ekow
 */
class GenerateCommand implements CommandInterface
{
    private $nyansapow;
    private $cacheFactory;

    public function __construct(Builder $nyansapow, CacheFactory $cacheFactory)
    {
        $this->nyansapow = $nyansapow;
        $this->cacheFactory = $cacheFactory;
    }


    public function execute($options)
    {
        $this->nyansapow->build($options, $this->cacheFactory);
    }

}
