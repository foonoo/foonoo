<?php

namespace foonoo\text;


enum TagToken: string {
    case COMMENT_START_TAG = "\\\\\[\[";
    case START_TAG = '\[\[';
    case END_TAG = '\]\]';
    case ARGS_LIST = '(?<identifier>[a-zA-Z][a-zA-Z0-9_\.\-]*)(\s*)(=)(\s*)';
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
                $raw = $delimiter;
                $index = 1;
                while($index < strlen($line) && $line[$index] != $delimiter) {
                    if ($line[$index] == "\\") {
                        $raw .= $line[$index];
                        $index +=1;
                        $raw .= $line[$index];
                        $string .= $line[$index];
                    } else {
                        $string .= $line[$index];
                        $raw .= $line[$index];
                    }
                    $index += 1;    
                }
                $raw .= $delimiter;
                $line = substr($line, strlen($raw));

                yield ['token' => TagToken::STRING, "value" => $string, 'matches' => [$string], "raw" => $raw];
            }
        }

        yield ['token' => TagToken::DONE, "value" => ""];
    }

    /**
     * Parse out all whitespace tokens.
     * @param $tokens
     */
    private function eatWhite(\Generator $tokens) : string
    {
        $whiteSpaces = "";
        while ($tokens->current()['token'] === TagToken::WHITESPACE) {
            $whiteSpaces .= $tokens->current()["value"];
            $tokens->next();
        }
        return $whiteSpaces;
    }

    private function parseAttributes(\Generator $tokens)
    {
        $attributes = [];
        $parsed = ""; // Keep a parsed string to pass on, so a failed tag can be regurgitated.

        while($tokens->current()['token'] == TagToken::ARGS_LIST) {
            $parsed .= $tokens->current()["value"];
            $key = trim($tokens->current()['matches']['identifier']);
            $tokens->next();
            if($tokens->current()['token'] == TagToken::STRING) {
                $parsed .= $tokens->current()["raw"];
                $attributes[$key] = $tokens->current()['value'];
            } else {
                $parsed .= $tokens->current()["value"];
            }
            $tokens->next();
            $parsed .= $this->eatWhite($tokens);
        }

        return [$attributes, $parsed];
    }

    /**
     * Process a foonoo tag by calling the associated tag callbacks.
     */
    private function processFoonooTag($matchedTokens, $tags, $passThrough) : string
    {
        foreach ($tags as $tag) {
            $args = [];
            $index = 0;
            foreach ($tag['definition'] as $key => $value) {
                if (is_string($value) && preg_match("/^{$value}/", $matchedTokens[$index]["value"], $matches))  {
                    $args[$key] = $matches;
                } else if ($value === TagToken::TEXT || $value === TagToken::ARGS_LIST) {
                    $args[$value == TagToken::ARGS_LIST ? "__args" : $key] = $matchedTokens[$index]["value"];
                } else {
                    break;
                }
                $index++;
            }
            if (count($args) == count($tag['definition'])) {
                return $tag['callable']($args);
            }
        }
        return $passThrough;
    }

    private function parseText(\Generator $tokens) {
        $parsed = "";
        $currentToken = $tokens->current();

        while(in_array($currentToken['token'], [TagToken::TEXT, TagToken::WHITESPACE])) {
            $parsed .= $currentToken['value'];
            $tokens->next();
            $currentToken = $tokens->current();
        }

        return $parsed;
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
        $lastToken = $currentToken;

        // Todo: Get a token and match the text with all registered tags
        while ($currentToken !== null && !in_array($currentToken['token'], [TagToken::END_TAG, TagToken::START_TAG, TagToken::DONE])) {

            // Because the ARGS_LIST is not matched in full through a regex, we have to treat it separately.
            if($currentToken['token'] == TagToken::SEPARATOR) {
                $matchedTokens[] = $lastToken;
            } else if ($currentToken['token'] == TagToken::TEXT) {
                $text = $this->parseText($tokens);
                $lastToken = [
                    'token' => TagToken::TEXT,
                    'value' => $text
                ];
                $currentToken = $tokens->current();
                $tag .= $text;
                continue;
            } else if ($currentToken['token'] == TagToken::ARGS_LIST) {
                list($attributes, $parsed) = $this->parseAttributes($tokens);
                $lastToken = [
                    'token' => TagToken::ARGS_LIST, 
                    'value' => $attributes
                ];
                $currentToken = $tokens->current();
                $tag .= $parsed;
                continue;
            } else if($currentToken['token'] != TagToken::WHITESPACE) {
                $lastToken = $currentToken;
            } 

            $tag .= $currentToken['value'];
            $tokens->next();
            $currentToken = $tokens->current();
        }

        if($currentToken['token'] == TagToken::END_TAG) {
            $matchedTokens[] = $lastToken;
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
     * Parse a all tags in a given line.
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
