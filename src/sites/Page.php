<?php


namespace nyansapow\sites;


use nyansapow\NyansapowException;
use Symfony\Component\Yaml\Exception\ParseException;
use \Symfony\Component\Yaml\Parser as YamlParser;

class Page
{
    private $source;
    private $destination;
    private $frontmatter;
    private $firstLineOfBody = 0;
    private $body;

    public function __construct($source, $destination, $fileInfo)
    {
        $this->source = $source;
        $this->destination = $destination;
        $this->frontmatter = $fileInfo['frontmatter'] ?? null;
        $this->firstLineOfBody = $fileInfo['first_line_of_body'] ?? null;
    }

    public function getFrontMatter()
    {
        return $this->frontmatter;
    }

    public function getBody()
    {
        if(!$this->body) {
            $body = '';
            $file = new \SplFileObject($this->source);
            $file->seek($this->firstLineOfBody);

            while(!$file->eof()) {
                $body .= $file->fgets();
            }
        }

        return $body;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getDestination()
    {
        return $this->destination;
    }
}
