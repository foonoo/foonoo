<?php

namespace nyansapow;

use clearice\io\Io;
use nyansapow\text\TemplateEngine;


abstract class Plugin
{
    private $options;
    private $io;
    private $name;

    public function __construct(string $name, Io $io, array $options)
    {
        $this->io = $io;
        $this->options = $options;
        $this->name = $name;
    }

    protected function getOption($option, $default = null)
    {
        return $this->options[$option] ?? $default;
    }

    public function stdOut($message, $verbosity = Io::OUTPUT_LEVEL_1)
    {
        $this->io->output($message, $verbosity);
    }

    public function errOut($message, $verbosity = Io::OUTPUT_LEVEL_1)
    {
        $this->io->error("{$this->name}: $message\n", $verbosity);
    }

    public abstract function getEvents();
}
