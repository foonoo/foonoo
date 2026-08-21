<?php

namespace foonoo\tests\Unit\Utils;

use foonoo\tests\BaseTestCase;
use foonoo\utils\Nomenclature;

class NomenclatureTest extends BaseTestCase
{
    private $nomenclatureHost;

    public function setUp(): void
    {
        parent::setUp();
        $this->nomenclatureHost = new class {
            use Nomenclature;

            public function callMakeId(string $text, array $ids = []): string
            {
                return $this->makeId($text, $ids);
            }

            public function callMakeLabel(string $text): string
            {
                return $this->makeLabel($text);
            }
        };
    }

    public function testMakeIdBasic(): void
    {
        $id = $this->nomenclatureHost->callMakeId('Hello World');
        $this->assertSame('hello-world', $id);
    }

    public function testMakeIdSpecialCharacters(): void
    {
        $id = $this->nomenclatureHost->callMakeId('Getting Started: Part 1 (2026)!');
        $this->assertSame('getting-started-part-1-2026-', $id);
    }

    public function testMakeIdPreservesPeriodsAndUnderscores(): void
    {
        $id = $this->nomenclatureHost->callMakeId('version_1.0.release');
        $this->assertSame('version_1.0.release', $id);
    }

    public function testMakeIdAvoidsCollision(): void
    {
        $existing = ['sample-title', 'sample-title0', 'sample-title1'];
        $id = $this->nomenclatureHost->callMakeId('Sample Title', $existing);
        $this->assertSame('sample-title2', $id);
    }

    public function testMakeLabel(): void
    {
        $label = $this->nomenclatureHost->callMakeLabel('hello-world-post');
        $this->assertSame('Hello world post', $label);

        $labelConsecutive = $this->nomenclatureHost->callMakeLabel('multiple---hyphens');
        $this->assertSame('Multiple hyphens', $labelConsecutive);
    }
}
