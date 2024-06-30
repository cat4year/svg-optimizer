<?php

declare(strict_types=1);

namespace SvgReuser\Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use PHPUnit\Framework\TestCase;
use SvgReuser\SvgException;
use SvgReuser\SvgStorage;

class SvgStorageTest extends TestCase
{
    private DOMDocument $dom;
    private SvgStorage $storage;

    protected function setUp(): void
    {
        $this->dom = new DOMDocument();
        $this->storage = new SvgStorage();
    }

    public function testIsValidLoadSprite(): void
    {
        try {
            $this->storage->loadSprite('../resources/images/sprite.svg');
            $this->assertTrue(true);
        } catch (SvgException $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testIsValidSpriteStructure(): void
    {
        $this->storage->loadSprite('../resources/images/sprite.svg');

        ob_start();
        $this->storage->showSprite(false);
        $content = ob_get_clean();

        $this->dom->preserveWhiteSpace = false;
        $this->dom->validateOnParse = true;
        $this->dom->loadXML($content);

        $svgNode = $this->dom->getElementsByTagName('svg')->item(0);
        $this->assertsForSvgSprite($svgNode);
    }

    private function assertsForSvgSprite($svgSpriteNode): void
    {
        $this->assertNotNull($svgSpriteNode);
        /** @var DOMElement $child */
        foreach ($svgSpriteNode->childNodes as $child) {
            $this->assertTrue(is_a($child, DOMElement::class));
            $this->assertSame($child->tagName, 'symbol');
            $this->assertTrue($child->hasAttribute('id'));
        }
    }

    private function assertsForSvgUse(
        $svgUseNode,
        string $id,
        string $overwriteClass = '',
        string $classFromFile = 'svg-first__class'
    ): void
    {
        $this->assertNotNull($svgUseNode);
        $this->assertTrue(is_a($svgUseNode, DOMElement::class));
        $this->assertSame($svgUseNode->tagName, 'svg');
        if ($overwriteClass !== '') {
            $this->assertSame($svgUseNode->getAttribute('class'), $overwriteClass);
        } else {
            $this->assertSame($svgUseNode->getAttribute('class'), $classFromFile);
        }

        $this->assertSame($svgUseNode->childNodes->length, 1);
        /** @var DOMElement $firstChild */
        $firstChild = $svgUseNode->firstChild;
        $this->assertSame($firstChild->tagName, 'use');
        $this->assertTrue($firstChild->hasAttribute('href'));
        $this->assertSame($firstChild->getAttribute('href'), "#$id");
    }

    public function testShowOneSvgOnlyById(): void
    {
        $this->storage->loadSprite('../resources/images/sprite.svg');
        $id = 'svg-first';

        ob_start();
        $this->storage->showSvg($id);
        $content = ob_get_clean();

        $this->dom->preserveWhiteSpace = false;
        $this->dom->validateOnParse = true;
        $this->dom->loadXML($content);

        /** @var DOMElement $svgUseNode */
        $svgUseNode = $this->dom->getElementsByTagName('svg')->item(0);
        $this->assertsForSvgUse($svgUseNode, $id);
    }

    public function testShowOneSvgWithOverwriteClass(): void
    {
        $this->storage->loadSprite('../resources/images/sprite.svg');
        $id = 'svg-first';

        ob_start();
        $this->storage->showSvg($id, 'svg-first-overwrite-class');
        $content = ob_get_clean();

        $this->dom->preserveWhiteSpace = false;
        $this->dom->validateOnParse = true;
        $this->dom->loadXML($content);

        /** @var DOMElement $svgUseNode */
        $svgUseNode = $this->dom->getElementsByTagName('svg')->item(0);
        $this->assertsForSvgUse($svgUseNode, $id, 'svg-first-overwrite-class');
    }

    public function testShowAnySvg(): void
    {
        $this->storage->loadSprite('../resources/images/sprite.svg');
        $ids = ['svg-first', 'svg-second'];
        ob_start();
        $this->storage->showSvg($ids[0]);
        $this->storage->showSvg($ids[1], 'svg-second-overwrite-class');
        $content = ob_get_clean();

        $this->dom->preserveWhiteSpace = false;
        $this->dom->validateOnParse = true;
        $this->dom->loadHTML($content);

        /** @var DOMElement $svgUseNode */
        $svgUseNode = $this->dom->getElementsByTagName('svg')->item(0);
        $this->assertsForSvgUse($svgUseNode, $ids[0]);

        $svgUseNode = $this->dom->getElementsByTagName('svg')->item(1);
        $this->assertsForSvgUse($svgUseNode, $ids[1], 'svg-second-overwrite-class');
    }

    public function testShowOptimizedSpriteByOnlyUsedSvg(): void
    {
        $this->storage->loadSprite('../resources/images/sprite.svg');
        $ids = ['svg-first', 'svg-second'];
        ob_start();
        $this->storage->showSvg($ids[0]);
        $this->storage->showSvg($ids[1]);
        ob_end_clean();
        ob_start();
        $this->storage->showSprite();
        $content = ob_get_clean();

        $this->dom->preserveWhiteSpace = false;
        $this->dom->validateOnParse = true;
        $this->dom->loadHTML($content);

        /** @var DOMElement $svgUseNode */
        $svgUseNode = $this->dom->getElementsByTagName('svg')->item(0);
        $this->assertsForSvgSprite($svgUseNode);
        $this->assertSame($svgUseNode->childNodes->length, 2);
    }
}
