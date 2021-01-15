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
     * @throws \Exception
     */
    public function execute(array $options = [])
    {
        if(isset($options['add-plugins-path'])) {
            $this->pluginManager->addPluginPaths(array_reverse($options['add-plugins-path']));
        }
        $this->builder->build($options, $this->cacheFactory, $this->pluginManager);
    }

}
