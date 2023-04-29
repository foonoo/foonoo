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

    public function testAttributes()
    {
        $response = $this->tagParser->parse("Hello [[world|caps|attributed=\"heey\"]], this is an interesting tag.");
        $this->assertEquals("Hello [WORLD]->[__default:augmented ], this is an interesting tag.\n", $response);
    }

    // public function testAttributes()
    // {
    //     $response = $this->tagParser->parse("Hello [[caps Args| key=value ]] arguments");
    //     $this->assertEquals("Hello [ARGS]->[key:value ] arguments\n", $response);
    //     $response = $this->tagParser->parse("Hello [[caps Args| key=value word=meaning ]] arguments");
    //     $this->assertEquals("Hello [ARGS]->[key:value word:meaning ] arguments\n", $response);
    // }

    // public function testCombinedAttributes()
    // {
    //     $response = $this->tagParser->parse("Combined attributes [[caps combined| Value of default | key=value]]. Yeah!");
    //     $this->assertEquals("Combined attributes [COMBINED]->[__default:Value of default key:value ]. Yeah!\n", $response);
    // }
}
