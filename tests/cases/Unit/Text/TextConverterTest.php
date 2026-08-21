<?php

namespace foonoo\tests\Unit\Text;

use foonoo\exceptions\FoonooException;
use foonoo\tests\BaseTestCase;
use foonoo\text\ConverterInterface;
use foonoo\text\TagParser;
use foonoo\text\TextConverter;

class TextConverterTest extends BaseTestCase
{
    private TagParser $tagParser;
    private TextConverter $textConverter;

    public function setUp(): void
    {
        parent::setUp();
        $this->tagParser = $this->createStub(TagParser::class);
        $this->tagParser->method('parse')->willReturnArgument(0);
        $this->textConverter = new TextConverter($this->tagParser);
    }

    public function testRegisterAndCheckConvertible(): void
    {
        $dummyConverter = new class implements ConverterInterface {
            public function convert($input): string
            {
                return strtoupper($input);
            }
        };

        $this->assertFalse($this->textConverter->isConvertible('txt', 'html'));
        $this->textConverter->registerConverter('txt', 'html', $dummyConverter);
        $this->assertTrue($this->textConverter->isConvertible('txt', 'html'));
        $this->assertFalse($this->textConverter->isConvertible('txt', 'pdf'));
    }

    public function testRegisterWithDotThrowsException(): void
    {
        $this->expectException(FoonooException::class);
        $this->expectExceptionMessage("Extension for source convertible content cannot contain a dot '.'");

        $dummyConverter = $this->createStub(ConverterInterface::class);
        $this->textConverter->registerConverter('.md', 'html', $dummyConverter);
    }

    public function testConvertThrowsExceptionWhenNotSupported(): void
    {
        $this->expectException(FoonooException::class);
        $this->expectExceptionMessage("There isn't a converter to convert rst to html");

        $this->textConverter->convert('Some text', 'rst', 'html');
    }

    public function testSuccessfulConvert(): void
    {
        $mockConverter = $this->createMock(ConverterInterface::class);
        $mockConverter->expects($this->once())
            ->method('convert')
            ->with('parsed input')
            ->willReturn('<b>parsed input</b>');

        $tagParser = $this->createMock(TagParser::class);
        $tagParser->expects($this->once())
            ->method('parse')
            ->with('raw input')
            ->willReturn('parsed input');

        $textConverter = new TextConverter($tagParser);
        $textConverter->registerConverter('md', 'html', $mockConverter);

        $result = $textConverter->convert('raw input', 'md', 'html');
        $this->assertSame('<b>parsed input</b>', $result);
    }
}
