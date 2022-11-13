<?php

namespace foonoo\sites;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

class FrontMatterReader
{

    private $yamlParser;

    public function __construct(Parser $yamlParser)
    {
        $this->yamlParser = $yamlParser;
    }

    public function read($path): array
    {
        $file = fopen($path, 'r');
        $frontMatter = [];
        $body = "";

        while (!feof($file)) {
            $line = fgets($file);
            if (trim($line) == "---") {
                $frontMatter = $this->extractAndDecodeFrontMatter($file, $path);
                break;
            } else {  
                $body .= $line;
                break;
            } 
        }

        while (!feof($file)) {
            $body .= fgets($file);
        }

        fclose($file);
        return [$frontMatter, $body];
    }

    private function extractAndDecodeFrontMatter($file, $path): array
    {
        $frontmatter = '';
        do {
            $line = fgets($file);
            if (trim($line) == "---") {
                break;                
            }
            $frontmatter .= $line;
        } while (!feof($file));

        try {
            return $this->yamlParser->parse($frontmatter);
        } catch (ParseException $e) {
            throw new ParseException("Could not parse frontmatter from {$path}: {$e->getMessage()}");
        }
    }

}
