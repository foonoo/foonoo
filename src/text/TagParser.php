<?php

namespace foonoo\text;

use foonoo\sites\AbstractSite;
use foonoo\content\Content;

/**
 * Parse text containing special Foonoo tags.
 *
 * Foonoo tags start with
 *
 * @package nyansapow
 */
class TagParser
{
    private $tags;

    /**
     * Register a foonoo Tag
     *
     * @param string $regex
     * @param int $priority
     * @param callable $callable
     * @param string|null $name
     */
    public function registerTag(string $regex, int $priority, callable $callable, string $name = null) : void
    {
        // Since input ($regex) is a full regex, find its boundaries and replace that with a regex that matches the
        // double square brackets at the beginning and end, and the attributes that come after the bar.
        $regex = substr_replace($regex, '\[\[', 1, 0);
        for($i = strlen($regex) - 1; $i>0; $i--) {
            if($regex[$i] == $regex[0]) break;
        }
        $regex = substr_replace($regex, '(|)?\]\]', $i, 0);
        $this->tags[] = ['regex' => $regex, 'priority' => $priority, 'callable' => $callable, 'name' => $name];
        usort($this->tags, function($a, $b) {return $a['priority'] - $b['priority'];});
    }

    /**
     * Parse any text line of text for foonoo tags.
     * Any tags found in the content are replaced with the expected output from the registered tag parser, and the final
     * line containing all the substitutions are returned.
     *
     * @param string $content
     * @return string
     */
    public function parse(string $content) : string
    {
        $parsed = '';
        foreach (explode("\n", $content) as $line) {
            if(trim($line) == "") {
                $parsed .= "\n";
                continue;
            }
            $parsed .= $this->parseLine($line) . "\n";
        }
        return $parsed;
    }

    /**
     * Parse a single tag from a line.
     *
     * @param $line
     * @return string|string[]|null
     */
    private function parseLine($line)
    {
        if (!preg_match("/\[\[/", $line, $matches, PREG_OFFSET_CAPTURE)) {
            return $line;
        }
        foreach ($this->tags as $tag) {
            $line = preg_replace_callback($tag['regex'], $tag['callable'], $line);
        }
        return $line;
    }
}
