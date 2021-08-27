<?php

namespace foonoo\text;

use foonoo\exceptions\FoonooException;

class TextConverter
{
    private $parser;
    private $converters;

    /**
     * HtmlRenderer constructor.
     * @param TagParser $parser
     */
    public function __construct(TagParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * A register a text converter.
     * 
     * @param string $from The extension type for convertible text content.
     * @param string $to The target extension for the conversion.
     * @param ConverterInterface $converter An instance of the converter.
     */
    public function registerConverter(string $from, string $to, ConverterInterface $converter) : void
    {
        if(strpos($from, '.') !== false) {
            throw new FoonooException("Extension for source convertible content cannot contain a dot '.'");
        }
        if(!isset($this->converters[$from])) {
            $this->converters[$from] = [];
        }
        $this->converters[$from][$to] = $converter;
    }
    
    /**
     * Check the convertibility between two file types.
     * 
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function isConvertible(string $from, string $to) : bool
    {
        return isset($this->converters[$from][$to]);
    }

    /**
     * Render text
     *
     * @param string $content
     * @param string $from
     * @param string $to
     * @return string
     * @throws FoonooException
     */
    public function convert(string $content, string $from, string $to) : string
    {
        if(!isset($this->converters[$from][$to])) {
            throw new FoonooException("There isn't a converter to convert $from to $to");
        }
        $converter = $this->converters[$from][$to];
        $content = $this->parser->parse($content);
        return $converter->convert($content);
    }
}

