<?php


namespace nyansapow\text;


interface ConverterInterface
{
    public function convert($input): string;
}