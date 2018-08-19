<?php

namespace nyansapow\processors;


use clearice\io\Io;
use nyansapow\Nyansapow;

class ProcessorFactory
{
    private $io;

    public function __construct(Io $io)
    {
        $this->io = $io;
    }

    public function create(Nyansapow $nyansapow, $settings = [], $directory = null)
    {
        $class = "\\nyansapow\\processors\\" . ucfirst($settings['type']);
        return new $class($nyansapow, $this->io, $settings, $directory);
    }
}
