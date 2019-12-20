<?php


namespace nyansapow;


abstract class Plugin
{
    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    protected function getOptions()
    {
        return $this->options;
    }

    public abstract function getEvents();
}