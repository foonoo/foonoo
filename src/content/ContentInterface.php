<?php


namespace nyansapow\content;


interface ContentInterface
{
    public function getMetaData(): array;
    public function render(): string;
    public function getDestination(): string;
}