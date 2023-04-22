<?php

namespace foonoo\text;

/**
 * This class provides the code for parsing special foonoo tags from text files. These tags start with a double square
 * brace and end with same.
 */
class TagParser
{
    // A list of all registered tags
    private $registeredTags;

    // A list of all tokens
    private const TOKENS = [
        'COMMENT_START_TAG' => '\\\[\[',
        'START_TAG' => '\[\[',
        'END_TAG' => '\]\]',
        'IDENTIFIER' => '([a-zA-Z_\.\-][a-zA-Z_\.\-]*)(\s*)(=)',
        'CHUNK' => '((?![\[\]\|])\S)+|\]|\[',
        'WHITESPACE' => '[\s]+',
        'SEPARATOR' => '\|',
    ];

    /**
     * Register a foonoo Tag.
     * This operation requires you to specify a regular expression for the tag, a priority, and a function for 
     * generating the tag's output.
     *
     * @param string $regex A regular expression
     * @param int $priority
     * @param callable $callable
     * @param string|null $name
     */
    public function registerTag(string $regex, int $priority, callable $callable, string $name): void
    {
        $this->registeredTags[] = ['regex' => $regex, 'priority' => $priority, 'callable' => $callable, 'name' => $name];
        usort($this->registeredTags, function ($a, $b) { return $b['priority'] - $a['priority'];});
    }

    /**
     * Parse any text line of text for foonoo tags.
     * Any tags found in the content are replaced with the expected output from the registered tag parser, and the final
     * line containing all the substitutions are returned.
     *
     * @param string $content
     * @return string
     */
    public function parse(string $content): string
    {
        $parsed = '';
        foreach (explode("\n", $content) as $line) {
            if (trim($line) == "") {
                $parsed .= "\n";
                continue;
            }
            $parsed .= $this->parseLine($line) . "\n";
        }
        return $parsed;
    }

    private function getTokens($line)
    {
        while (strlen($line) > 0) {
            foreach (self::TOKENS as $token => $regex) {
                if (preg_match("%^($regex)%", $line, $matches)) {
                    $line = substr($line, strlen($matches[0]));
                    yield ['token' => $token, 'value' => $matches[0]];
                    break;
                }
            }
        }
        yield ['token' => 'END'];
    }

    private function processTag($text, $attributes)
    {
        foreach ($this->registeredTags as $tag) {
            if (preg_match("/^{$tag['regex']}/", $text, $matches)) {
                return $tag['callable']($matches, $text, $attributes);
            }
        }
    }

    /**
     * @param $tokens
     */
    private function eatWhite(\Generator $tokens)
    {
        while ($tokens->current()['token'] == 'WHITESPACE') {
            $tokens->next();
        }
    }

    private function parseAttributeTags(\Generator $tokens)
    {
        $attributes = [];
        while($tokens->current()['token'] == 'IDENTIFIER') {
            $key = trim(substr($tokens->current()['value'], 0, -1));
            $tokens->next();
            $attributes[$key] = $tokens->current()['value'];
            $tokens->next();
            $this->eatWhite($tokens);
        }
        return $attributes;
    }

    private function parseDefaultAttribute($tokens) {
        $tag = "";
        $currentToken = $tokens->current();
        while (!in_array($currentToken['token'], ['SEPARATOR', 'END_TAG', 'START_TAG', 'END'])) {
            $tag .= $currentToken['value'];
            $tokens->next();
            $currentToken = $tokens->current();
        }
        return ['__default' => trim($tag)];
    }

    /**
     * @param \Generator $tokens
     */
    private function parseAttributes(\Generator $tokens)
    {
        $tokens->next();
        $this->eatWhite($tokens);
        if ($tokens->current()['token'] == "IDENTIFIER") {
            return $this->parseAttributeTags($tokens);
        } else {
            $attributes = $this->parseDefaultAttribute($tokens);
            if($tokens->current()['token'] == "SEPARATOR") {
                $attributes = $attributes + $this->parseAttributes($tokens);
            }
            return $attributes;
        }
    }

    /**
     * @param $tokens
     */
    private function parseFoonooTag(\Generator $tokens)
    {
        $tokens->next();
        $tag = "";
        $currentToken = $tokens->current();
        while (!in_array($currentToken['token'], ['SEPARATOR', 'END_TAG', 'START_TAG', 'END'])) {
            $tag .= $currentToken['value'];
            $tokens->next();
            $currentToken = $tokens->current();
        }

        if ($currentToken['token'] == 'END_TAG') {
            // Process tag on end tag
            return $this->processTag($tag, []);
        } else if ($currentToken['token'] == 'SEPARATOR') {
            // Parse attributes on separator
            $attributes = $this->parseAttributes($tokens);
            if($tokens->current()['token'] == 'END_TAG') {
                return $this->processTag($tag, $attributes);
            }
        } else if ($currentToken['token'] == 'START_TAG') {
            // Recursively parse a new tag for incomplete tags
            return "[[" . $tag . $this->parseFoonooTag($tokens);
        }

        return "[[" . $tag;
    }

    /**
     * Parse a single tag from a line.
     *
     * @param $line
     * @return string|string[]|null
     */
    private function parseLine($line)
    {
        $output = "";
        $tokens = $this->getTokens($line);
        while ($tokens->current() !== null && $tokens->current()['token'] != 'END') {
            $currentToken = $tokens->current();
            if ($currentToken['token'] == 'START_TAG') {
                $output .= $this->parseFoonooTag($tokens);
            } else {
                $output .= $currentToken['value'];
            }
            $tokens->next();
        }
        return $output;
    }
}
