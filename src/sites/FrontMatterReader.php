<?php


namespace foonoo\sites;


use Symfony\Component\Yaml\Parser;

class FrontMatterReader
{
    private $yamlParser;

    public function __construct(Parser $yamlParser)
    {
        $this->yamlParser = $yamlParser;
    }

    public function read($path, &$linesRead = 0) : array
    {
        $file = fopen($path, 'r');
        $frontMatter = [];

        while (!feof($file)) {
            $line = fgets($file);
            if (trim($line) == "---") {
                $frontMatter = $this->extractAndDecodeFrontMatter($file, $linesRead);
                break;
            } else if (trim($line) != "") {
                $linesRead = 0;
                break;
            } else {
                $linesRead += 1;
            }
        }
        return $frontMatter;
    }

    private function extractAndDecodeFrontMatter($file, &$linesRead) : array
    {
        $frontmatter = '';
        do {
            $line = fgets($file);
            $linesRead += 1;
            if (trim($line) == "---") break;
            $frontmatter .= $line;
        } while (!feof($file));

        return $this->yamlParser->parse($frontmatter);
    }
}