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
    private $registeredTags;

    private const TOKENS = [
        'START_TAG' => '\[\[',
        'END_TAG' => '\]\]',
        'CHUNK' => '((?![\[\]])\S)+|\]|\[',
        'WHITESPACE' => '[\s]+',
        'SEPARATOR' => '|',
        'IDENTIFIER' => '[a-zA-Z_\.-][a-zA-Z_\.-]+(\s+)?(=)',
    ];

    /**
     * Register a foonoo Tag
     *
     * @param string $regex
     * @param int $priority
     * @param callable $callable
     * @param string|null $name
     */
    public function registerTag(string $regex, int $priority, callable $callable, string $name = null): void
    {
        $this->registeredTags[] = ['regex' => $regex, 'priority' => $priority, 'callable' => $callable, 'name' => $name];
        usort($this->registeredTags, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });
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

    private function processTag($text, $attributes) {
        foreach($this->registeredTags as $tag) {
            if (preg_match($tag['regex'], $text, $matches)) {
                return $tag['callable']($matches, $text, $attributes);
            }
        }
    }

    /**
     * @param $tokens
     */
    private function eatWhite(\Generator $tokens) {
        while($tokens->current()['token'] == 'WHITESPACE') {
            $tokens->next();
        }
    }

    /**
     * @param \Generator $tokens
     */
    private function parseAttributes(\Generator $tokens) {
        $this->eatWhite($tokens);
        if ($tokens->current()['token'] == "IDENTIFIER") {
            $this->eatWhite($tokens);

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
        } else if($currentToken['token'] == 'SEPARATOR') {
            // Parse attributes on separator
            return $this->processTag($tag, $this->parseAttributes($tokens));
        } else if ($currentToken['token'] == 'START_TAG') {
            // Recursively parse a new tag for incomplete tags
            return "[[" . $tag .  $this->parseFoonooTag($tokens);
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
//    private function parseLine($line)
//    {
//        $parsed = "";
//        $offset = 0;
//        while($offset < strlen($line)) {
//            $buffer = substr($line, $offset);
//            if (!preg_match("/\[\[/", $buffer, $matches, PREG_OFFSET_CAPTURE)) {
//                return $parsed . $buffer;
//            }
//            $start = $matches[0][1];
//            if(!preg_match("/\]\]/", $buffer, $matches, PREG_OFFSET_CAPTURE)) {
//                return $parsed . $buffer;
//            }
//            $parsed .= substr($buffer, 0, $start);
//            $start += 2;
//            $text = substr($buffer, $start, $matches[0][1] - $start);
//            $offset += $matches[0][1] + 2;
//            foreach($this->tags as $tag) {
//                if (preg_match($tag['regex'], $text, $matches)) {
//                    $parsed .= $tag['callable']($matches, $text);
//                    break;
//                }
//            }
//        }
//        return $parsed;
//    }
}
