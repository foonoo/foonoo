<?php

namespace nyansapow\sites;

use nyansapow\text\TextProcessors;
use Symfony\Component\Yaml\Parser;

class PageFactory
{
    private $textProcessors;
    private $yamlParser;

    public function __construct(TextProcessors $textProcessors, Parser $yamlParser)
    {
        $this->textProcessors = $textProcessors;
        $this->yamlParser = $yamlParser;
    }

    /**
     * Create a new Page object
     *
     * @param $source
     * @param $destination
     * @param bool $autoConvert
     * @return Page
     */
    public function create($source, $destination, $autoConvert = true) : Page
    {
        $fileInfo = null;
        if($autoConvert && $this->textProcessors->isFileRenderable($source)) {
            $destination = $this->adjustExtension($destination);
            $fileInfo = $this->getFileInfo($source);
        }
        return new Page($source, $destination, $fileInfo);
    }

    private function adjustExtension($file)
    {
        $path = explode('.', $file);
        $path[count($path) - 1] = 'html';
        return implode('.', $path);
    }

    private function getFileInfo($path) : array
    {
        $file = fopen($path, 'r');
        $isFrontmatterRead = false;
        $postStarted = false;
        $lineNumber = 0;
        $frontmatter = [];

        while (!feof($file)) {
            $line = fgets($file);
            if (!$isFrontmatterRead && !$postStarted && trim($line) == "---") {
                $frontmatter = $this->readFrontMatter($file);
                break;
            }
            $postStarted = true;
            $lineNumber++;
        }

        return ['frontmatter' => $frontmatter, 'first_line_of_body' => $lineNumber];
    }

    private function readFrontMatter($file)
    {
        $frontmatter = '';
        do {
            $line = fgets($file);
            if (trim($line) == "---") break;
            $frontmatter .= $line;
        } while (!feof($file));

        return $this->yamlParser->parse($frontmatter);
    }
}
