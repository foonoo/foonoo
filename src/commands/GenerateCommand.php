<?php

namespace nyansapow\commands;

use nyansapow\CacheFactory;
use nyansapow\CommandInterface;
use nyansapow\events\PluginsInitialized;
use nyansapow\Builder;

/**
 * Description of GenerateCommand
 *
 * @author ekow
 */
class GenerateCommand implements CommandInterface
{
    private $nyansapow;
    private $pluginsInitializedEvent;
    private $cacheFactory;

    public function __construct(Builder $nyansapow, PluginsInitialized $pluginsInitializedEvent, CacheFactory $cacheFactory)
    {
        $this->nyansapow = $nyansapow;
        $this->pluginsInitializedEvent = $pluginsInitializedEvent;
        $this->cacheFactory = $cacheFactory;
    }


    public function execute($options)
    {
        $this->nyansapow->build($options, $this->pluginsInitializedEvent, $this->cacheFactory);
    }

}
