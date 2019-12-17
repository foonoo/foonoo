<?php

namespace nyansapow\text;

use nyansapow\sites\AbstractSite;
use nyansapow\sites\ContentInterface;
use nyansapow\text\TemplateEngine;
use nyansapow\TocRequestedException;

/**
 * Parse text containing special Nyansapow tags.
 *
 * @package nyansapow
 */
class TagParser
{
    private $tags;

    public function registerTag(string $regex, int $priority, callable $callable, string $name = null) : void
    {
        $this->tags[] = ['regex' => $regex, 'priority' => $priority, 'callable' => $callable, 'name' => $name];
        usort($this->tags, function($a, $b) {return $a['priority'] - $b['priority'];});
    }

    /**
     * @param $content
     * @param $site
     * @param null $page
     * @return string
     */
    public function parse(string $content, AbstractSite $site, ContentInterface $page = null) : string
    {
        $parsed = '';
        foreach (explode("\n", $content) as $line) {
            $parsed .= $this->parseLine($line, $site, $page) . "\n";
        }
        return $parsed;
    }

    private function parseLine($line, $site, $page)
    {
        foreach ($this->tags as $tag) {
            $line = preg_replace_callback($tag['regex'], $tag['callable']($site, $page), $line);
        }

        return $line;
    }
}
