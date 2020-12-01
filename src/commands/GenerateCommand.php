<?php

namespace foonoo\commands;

use foonoo\CacheFactory;
use foonoo\CommandInterface;
use foonoo\Builder;

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
     * Instanec of the cache factory.
     * @var CacheFactory
     */
    private $cacheFactory;

    /**
     * Create the generate command
     *
     * @param Builder $builder
     * @param CacheFactory $cacheFactory
     */
    public function __construct(Builder $builder, CacheFactory $cacheFactory)
    {
        $this->builder = $builder;
        $this->cacheFactory = $cacheFactory;
    }

    /**
     * Start the site builder
     *
     * @param $options
     */
    public function execute(array $options = [])
    {
        $this->builder->build($options, $this->cacheFactory);
    }

}
