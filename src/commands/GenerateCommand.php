<?php

namespace foonoo\commands;

use foonoo\CacheFactory;
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
    private $builder;

    /**
     * Instance of the cache factory.
     * @var CacheFactory
     */
    private $cacheFactory;

    /**
     * Instance of the plugin manager;
     * @var PluginManager;
     */
    private $pluginManager;

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
     */
    public function execute(array $options = [])
    {
        $this->builder->build($options, $this->cacheFactory, $this->pluginManager);
    }

}
