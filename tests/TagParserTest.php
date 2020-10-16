<?php
namespace foonoo\tests;

use foonoo\text\TagParser;
use PHPUnit\Framework\TestCase;

class TagParserTest extends TestCase
{
    private $tagParser;

    public function setUp(): void
    {
        $this->tagParser = new TagParser();
        $this->tagParser->registerTag("/caps (.*)/", 0, function($matches) {return $this->replaceText($matches);}, "test");
    }

    private function replaceText($matches) {
        return strtoupper($matches[1]);
    }

    public function testTagRegistration()
    {
        $response = $this->tagParser->parse("Hello [[caps world]], this is an interesting tag.");
        $this->assertEquals("Hello WORLD, this is an interesting tag.\n", $response);
    }

    public function testMultiTags()
    {
        $response = $this->tagParser->parse("Hello [[caps world]], this is an interesting [[caps tag]].");
        $this->assertEquals("Hello WORLD, this is an interesting TAG.\n", $response);
    }

    public function testIncompleteTags()
    {
        $response = $this->tagParser->parse("Hello [[caps world, this is an interesting tag.");
        $this->assertEquals("Hello [[caps world, this is an interesting tag.\n", $response);
        $response = $this->tagParser->parse("Hello [[caps world]], this is an interesting [[caps tag.");
        $this->assertEquals("Hello WORLD, this is an interesting [[caps tag.\n", $response);
    }

}
