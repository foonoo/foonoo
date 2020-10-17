<?php
namespace foonoo\tests;

use foonoo\text\TagParser;
use PHPUnit\Framework\TestCase;

class TagParserTest extends TestCase
{
    private $tagParser;
    private $attributeParser;

    public function setUp(): void
    {
        $this->tagParser = new TagParser();
        $this->tagParser->registerTag("/caps (.*)/", 0,
            function($matches, $text, $attributes = []) {
                $attrs = "";
                foreach($attributes as $attribute => $value) {
                    $attrs .= "$attribute:$value ";
                }
                return "[{$this->replaceText($matches)}]->[$attrs]";
            }, "test");

    }

    private function replaceText($matches) {
        return strtoupper($matches[1]);
    }

    public function testTagRegistration()
    {
        $response = $this->tagParser->parse("Hello [[caps world]], this is an interesting tag.");
        $this->assertEquals("Hello [WORLD]->[], this is an interesting tag.\n", $response);
    }

    public function testMultiTags()
    {
        $response = $this->tagParser->parse("Hello [[caps world]], this is an interesting [[caps tag]].");
        $this->assertEquals("Hello [WORLD]->[], this is an interesting [TAG]->[].\n", $response);
    }

    public function testIncompleteTags()
    {
        $response = $this->tagParser->parse("Hello [[caps world, this is an interesting tag.");
        $this->assertEquals("Hello [[caps world, this is an interesting tag.\n", $response);
        $response = $this->tagParser->parse("Hello [[caps world]], this is an interesting [[caps tag.");
        $this->assertEquals("Hello [WORLD]->[], this is an interesting [[caps tag.\n", $response);
        $response = $this->tagParser->parse("Hello [[caps world, this is an interesting [[caps tag]].");
        $this->assertEquals("Hello [[caps world, this is an interesting [TAG]->[].\n", $response);
    }

    public function testDefaultAttributes()
    {
        $response = $this->tagParser->parse("Hello [[caps world|augmented]], this is an interesting tag.");
        $this->assertEquals("Hello [WORLD]->[__default:augmented ], this is an interesting tag.\n", $response);
    }

    public function testAttributes()
    {
        $response = $this->tagParser->parse("Hello [[caps Args| key=value ]] arguments");
        $this->assertEquals("Hello [ARGS]->[key:value ] arguments\n", $response);
        $response = $this->tagParser->parse("Hello [[caps Args| key=value word=meaning ]] arguments");
        $this->assertEquals("Hello [ARGS]->[key:value word:meaning ] arguments\n", $response);
    }

    public function testCombinedAttributes()
    {
        $response = $this->tagParser->parse("Combined attributes [[caps combined| Value of default | key=value]]. Yeah!");
        $this->assertEquals("Combined attributes [COMBINED]->[__default:Value of default key:value ]. Yeah!\n", $response);
    }
}
