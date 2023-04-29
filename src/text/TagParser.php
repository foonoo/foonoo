<?php

namespace foonoo\text;


enum TagToken: string {
    case COMMENT_START_TAG = "\\\\\[\[";
    case START_TAG = '\[\[';
    case END_TAG = '\]\]';
    case ARGS_LIST = '(?<identifier>[a-zA-Z][a-zA-Z0-9_\.\-]*)(\s*)(=)(\s*)(\'|")';
    case TEXT = '((?![\[\]\|])\S)+|\]|\[';
    case WHITESPACE = '[\s]+';
    case SEPARATOR = '\|';
    case DONE = "DONE";
    case STRING = "STRING";
}


/**
 * This class provides the code for parsing special foonoo tags from text files. These tags start with a double square
 * brace and end with same.
 */
class TagParser
{
    // A list of all registered tags
    private $registeredTags;

    /**
     * Register a foonoo Tag.
     * This operation requires you to specify a regular expression for the tag, a priority, and a function for 
     * generating the tag's output.
     *
     * @param array $definition A regular expression
     * @param int $priority
     * @param callable $callable
     * @param string|null $name
     */
    public function registerTag(array $definition, int $priority, callable $callable, string $name): void
    {
        $this->registeredTags[] = ['definition' => $definition, 'priority' => $priority, 'callable' => $callable, 'name' => $name];
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
        $parsed = [];
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (trim($line) == "") {
                $parsed .= "\n";
                continue;
            }
            $parsed[]= $this->parseLine($line);
        }
        return implode("\n", $parsed);
    }

    private function getTokens($line)
    {
        while (strlen($line) > 0) {
            foreach (TagToken::cases() as $token) {
                $regex = $token->value;
                if (preg_match("%^($regex)%", $line, $matches)) {
                    $line = substr($line, strlen($matches[0]));
                    yield ['token' => $token, 'value' => $matches[0], 'matches' => $matches];
                    break;
                }
            }

            // Parse strings here
            if(strlen($line) > 0 && ($line[0] === "'" || $line[0] === '"')) {
                $string = "";
                $delimiter = $line[0];
                $escaped = false;
                $index = 1;
                while(!$escaped && $line[$index] != $delimiter) {
                    $string .= $line[$index];
                    $index += 1;
                }

                yield ['token' => TagToken::STRING, "value" => $string, 'matches' => [$string]];
            }
        }

        yield ['token' => TagToken::DONE, "value" => ""];
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

    private function parseAttributes(\Generator $tokens)
    {
        $attributes = [];
        while($tokens->current()['token'] == TagToken::ARGS_LIST) {
            $key = trim(substr($tokens->current()['matches']['identifier'], 0, -1));
            $tokens->next();
            $attributes[$key] = $tokens->current()['value'];
            $tokens->next();
            $this->eatWhite($tokens);
        }
        return $attributes;
    }

    private function processFoonooTag($matchedTokens, $tags, $passThrough) : string
    {
        foreach ($tags as $tag) {
            $args = [];
            foreach ($tag['definition'] as $key => $value) {
                if (is_string($value) && preg_match("/^{$value}/", $matchedTokens[$key]["value"], $matches))  {
                    $args[] = $matches;
                } else if ($value === TagToken::TEXT) {
                    $args[] = $matchedTokens[$key]["value"];
                } else {
                    break;
                }
            }
            if (count($args) == count($tag['definition'])) {
                return $tag['callable']($args);
            }
        }

        return $passThrough;
    }

    /**
     * Parses any detected foonoo tags.
     * @param $tokens
     */
    private function parseFoonooTag(\Generator $tokens) : string
    {
        $tokens->next();
        $tag = "";
        $currentToken = $tokens->current();
        $matchedTokens = [];
        $lasToken = null;

        // Todo: Get a token and match the text with all registered tags
        while ($currentToken !== null && !in_array($currentToken['token'], [TagToken::END_TAG, TagToken::START_TAG, TagToken::DONE])) {
            $tag .= $currentToken['value'];

            if($currentToken['token'] == TagToken::SEPARATOR) {
                $matchedTokens[] = $lasToken;
            } else if ($currentToken['token'] == TagToken::ARGS_LIST) {
                $this->parseAttributes($tokens);
            } else if($currentToken['token'] != TagToken::WHITESPACE) {
                $lasToken = $currentToken;
            }

            $tokens->next();
            $currentToken = $tokens->current();
        }

        if($currentToken['token'] == TagToken::END_TAG) {
            $matchedTokens[] = $lasToken;
            $compatibleTags = array_filter(
                $this->registeredTags, 
                fn($tag) => count($tag['definition']) == count($matchedTokens)
            );
            return $this->processFoonooTag($matchedTokens, $compatibleTags, $tag);
        } else if ($currentToken['token'] == TagToken::START_TAG) {
            // Start parsing a new tag for incomplete tags
            return "[[$tag{$this->parseFoonooTag($tokens)}";
        }

        return "[[$tag" . ($currentToken !== null ? $currentToken['value'] : "");
    }

    /**
     * Parse a single tag from a line.
     *
     * @param $line
     * @return string|string[]|null
     */
    private function parseLine($line) : string
    {
        $output = "";
        $tokens = $this->getTokens($line);
        while ($tokens->current() !== null && $tokens->current()['token'] != TagToken::DONE) {
            $currentToken = $tokens->current();
            $output .= match($currentToken['token']) {
                TagToken::START_TAG => $this->parseFoonooTag($tokens),
                TagToken::COMMENT_START_TAG => "[[",
                default => $currentToken['value']
            };
            $tokens->next();
        }
        return $output;
    }
}
