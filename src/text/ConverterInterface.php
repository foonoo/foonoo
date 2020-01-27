<?php


namespace nyansapow\text;


interface ConverterInterface
{
    public function convert(string $input): string;
}