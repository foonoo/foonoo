<?php

namespace nyansapow\text;

use nyansapow\sites\AbstractSite;
use nyansapow\content\Content;

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
        $regex = substr_replace($regex, '\[\[', 1, 0);
        for($i = strlen($regex) - 1; $i>0; $i--) {
            if($regex[$i] == $regex[0]) break;
        }
        $regex = substr_replace($regex, '\]\]', $i, 0);
        $this->tags[] = ['regex' => $regex, 'priority' => $priority, 'callable' => $callable, 'name' => $name];
        usort($this->tags, function($a, $b) {return $a['priority'] - $b['priority'];});
    }

    /**
     * @param string $content
     * @return string
     */
    public function parse(string $content) : string
    {
        $parsed = '';
        foreach (explode("\n", $content) as $line) {
            $parsed .= $this->parseLine($line) . "\n";
        }
        return $parsed;
    }

    private function parseLine($line)
    {
        foreach ($this->tags as $tag) {
            $line = preg_replace_callback($tag['regex'], $tag['callable'], $line);
        }
        return $line;
    }

}
