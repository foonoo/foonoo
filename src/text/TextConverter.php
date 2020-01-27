<?php

namespace nyansapow\text;

use nyansapow\content\PreprocessableInterface;
use nyansapow\NyansapowException;
use nyansapow\sites\AbstractSite;
use nyansapow\content\Content;

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

    public function registerConverter(string $from, string $to, ConverterInterface $converter)
    {
        if(!isset($this->converters[$from])) {
            $this->converters[$from] = [];
        }
        $this->converters[$from][$to] = $converter;
    }

    /**
     * Render text
     *
     * @param string $content
     * @param string $from
     * @param string $to
     * @return string
     * @throws NyansapowException
     */
    public function convert(string $content, string $from, string $to) // Content $page=null)
    {
        if(!isset($this->converters[$from][$to])) {
            throw new NyansapowException("There isn't a converter to convert $from to $to");
        }
        $converter = $this->converters[$from][$to];
        if(is_a($converter, PreprocessableInterface::class, )) {
            $content = $this->parser->parse($content);
        }
        return $converter->convert($content);
    }
}

