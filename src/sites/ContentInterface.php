<?php


namespace nyansapow\sites;


interface ContentInterface
{
    public function render(): string;
    public function getDestination(): string;
}