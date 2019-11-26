<?php


namespace nyansapow\sites;


interface ContentInterface
{
    public function getMetaData(): array;
    public function render(): string;
    public function getDestination(): string;
}