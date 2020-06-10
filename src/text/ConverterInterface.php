<?php


namespace foonoo\text;


interface ConverterInterface
{
    public function convert($input): string;
}