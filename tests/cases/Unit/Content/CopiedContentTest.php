<?php

namespace foonoo\tests\Unit\Content;

use foonoo\content\CopiedContent;
use foonoo\tests\BaseTestCase;

class CopiedContentTest extends BaseTestCase
{
    public function testCopiedContent(): void
    {
        $sourceFile = $this->createVfsFile('assets/logo.png', 'fake_png_data');
        $content = new CopiedContent($sourceFile, 'output/assets/logo.png');

        $this->assertSame('fake_png_data', $content->render());
        $this->assertSame('output/assets/logo.png', $content->getDestination());
        $this->assertSame(['layout' => false], $content->getMetaData());
        $this->assertNotEmpty($content->getID());
    }
}
