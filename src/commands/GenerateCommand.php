<?php

namespace nyansapow\commands;

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

    public function __construct(Builder $nyansapow, PluginsInitialized $pluginsInitializedEvent)
    {
        $this->nyansapow = $nyansapow;
        $this->pluginsInitializedEvent = $pluginsInitializedEvent;
    }


    public function execute($options)
    {
        $this->nyansapow->write($options, $this->pluginsInitializedEvent);
    }

}
