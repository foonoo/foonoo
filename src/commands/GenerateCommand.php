<?php

namespace foonoo\commands;

use foonoo\utils\CacheFactory;
use foonoo\CommandInterface;
use foonoo\Builder;
use foonoo\PluginManager;

/**
 * Implements the "generate" command used for building sites.
 *
 * @author ekow
 */
class GenerateCommand implements CommandInterface
{
    /**
     * Instance of the site builder.
     * @var Builder
     */
    private Builder $builder;

    /**
     * Instance of the cache factory.
     * @var CacheFactory
     */
    private CacheFactory $cacheFactory;

    /**
     * Instance of the plugin manager;
     * @var PluginManager;
     */
    private PluginManager $pluginManager;

    /**
     * Create the generate command
     *
     * @param Builder $builder
     * @param CacheFactory $cacheFactory
     * @param PluginManager $pluginManager
     */
    public function __construct(Builder $builder, CacheFactory $cacheFactory, PluginManager $pluginManager)
    {
        $this->builder = $builder;
        $this->cacheFactory = $cacheFactory;
        $this->pluginManager = $pluginManager;
    }

    /**
     * Start the site builder
     *
     * @param $options
     * @throws \Exception
     */
    public function execute(array $options = []): void
    {
        if(isset($options['plugin-path'])) {
            $this->pluginManager->addPluginPaths(array_reverse($options['plugin-path']));
        }
        $this->builder->build($options, $this->cacheFactory, $this->pluginManager);
    }

}
