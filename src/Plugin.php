<?php
namespace foonoo;

use clearice\io\Io;


abstract class Plugin
{
    private array $options;
    private Io $io;
    private string $name;

    public function __construct(string $name, Io $io, array $options)
    {
        $this->io = $io;
        $this->options = $options;
        $this->name = $name;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    protected function getOption(string $option, mixed $default = null): mixed
    {
        return $this->options[$option] ?? $default;
    }

    protected function stdOut(string $message, int $verbosity = Io::OUTPUT_LEVEL_2)
    {
        $this->io->output("  - [{$this->name}] $message", $verbosity);
    }

    protected function errOut(string $message, int $verbosity = Io::OUTPUT_LEVEL_2)
    {
        $this->io->error("  - [{$this->name}] $message", $verbosity);
    }

    public abstract function getEvents();
}
