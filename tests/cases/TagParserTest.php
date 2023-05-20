<?php
namespace foonoo\tests;

use foonoo\text\TagParser;
use foonoo\text\TagToken;
use PHPUnit\Framework\TestCase;

class TagParserTest extends TestCase
{
    private $tagParser;

    public function setUp(): void
    {
        $this->tagParser = new TagParser();
        $this->tagParser->registerTag([TagToken::TEXT, "caps"], 0, fn($matches) => json_encode($matches), "test");
        $this->tagParser->registerTag([TagToken::TEXT, "caps", TagToken::ARGS_LIST], 1, fn($matches) => json_encode($matches), "test");
    }

    public function testTagRegistration()
    {
        $response = $this->tagParser->parse("Hello [[world|caps]], this is an interesting tag.");
        $this->assertEquals('Hello ["world",["caps"]], this is an interesting tag.', $response);
    }

    public function testMultiTags()
    {
        $response = $this->tagParser->parse("Hello [[world|caps]], this is an interesting [[tag|caps]].");
        $this->assertEquals('Hello ["world",["caps"]], this is an interesting ["tag",["caps"]].', $response);
    }

    public function testIncompleteTags()
    {
        $response = $this->tagParser->parse("Hello [[world|caps , this is an interesting tag.");
        $this->assertEquals("Hello [[world|caps , this is an interesting tag.", $response);
        $response = $this->tagParser->parse("Hello [[world|caps]], this is an interesting [[tag|caps.");
        $this->assertEquals('Hello ["world",["caps"]], this is an interesting [[tag|caps.', $response);
        $response = $this->tagParser->parse("Hello [[world|caps, this is an interesting [[tag|caps]].");
        $this->assertEquals('Hello [[world|caps, this is an interesting ["tag",["caps"]].', $response);
    }

    public function testCommentTag()
    {
        $response = $this->tagParser->parse("Hello \[[world|caps]], this is an interesting tag.");
        $this->assertEquals('Hello [[world|caps]], this is an interesting tag.', $response);
    }

    public function testAttributesSingleQuote()
    {
        $response = $this->tagParser->parse("Hello [[world|caps|attributed=\"heey\"]], this is an interesting tag.");
        $this->assertEquals('Hello ["world",["caps"],{"attributed":"heey"}], this is an interesting tag.', $response);
        $response = $this->tagParser->parse("Hello [[world|caps| key=\"value\" word=\"meaning\" ]] arguments");
        $this->assertEquals('Hello ["world",["caps"],{"key":"value","word":"meaning"}] arguments', $response);
    }

    public function testAttributesDoubleQuote()
    {
        $response = $this->tagParser->parse("Hello [[world|caps|attributed='heey']], this is an interesting tag.");
        $this->assertEquals('Hello ["world",["caps"],{"attributed":"heey"}], this is an interesting tag.', $response);
        $response = $this->tagParser->parse("Hello [[world|caps| key='value' word='meaning' ]] arguments");
        $this->assertEquals('Hello ["world",["caps"],{"key":"value","word":"meaning"}] arguments', $response);
    }

    public function testAttributesQuoteEscape()
    {
        $response = $this->tagParser->parse("Hello [[world|caps|attributed=\"He's \\\"Quoted\\\" \"]], this is an interesting tag.");
        $this->assertEquals('Hello ["world",["caps"],{"attributed":"He\'s \"Quoted\" "}], this is an interesting tag.', $response);
        $response = $this->tagParser->parse('Hello [[world|caps|attributed=\'He\\\'s "Quoted" \']], this is an interesting tag.');
        $this->assertEquals('Hello ["world",["caps"],{"attributed":"He\'s \"Quoted\" "}], this is an interesting tag.', $response);
    }

}
