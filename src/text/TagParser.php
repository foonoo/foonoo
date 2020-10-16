<?php

namespace foonoo\text;

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
        //$regex = substr_replace($regex, '\[\[', 1, 0);
        //for($i = strlen($regex) - 1; $i>0; $i--) {
        //    if($regex[$i] == $regex[0]) break;
        //}
        //$regex = substr_replace($regex, '(|)?\]\]', $i, 0);
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
        $parsed = "";
        $offset = 0;
        while($offset < strlen($line)) {
            $buffer = substr($line, $offset);
            if (!preg_match("/\[\[/", $buffer, $matches, PREG_OFFSET_CAPTURE)) {
                return $parsed . $buffer;
            }
            $start = $matches[0][1];
            if(!preg_match("/\]\]/", $buffer, $matches, PREG_OFFSET_CAPTURE)) {
                return $parsed . $buffer;
            }
            $parsed .= substr($buffer, 0, $start);
            $start += 2;
            $text = substr($buffer, $start, $matches[0][1] - $start);
            $offset += $matches[0][1] + 2;
            foreach($this->tags as $tag) {
                if (preg_match($tag['regex'], $text, $matches)) {
                    $parsed .= $tag['callable']($matches, $text);
                    break;
                }
            }
        }
        return $parsed;
    }
}
