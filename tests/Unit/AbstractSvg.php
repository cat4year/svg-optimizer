<?php

declare(strict_types=1);

namespace SvgReuser\Tests\Unit;

use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;
use SvgReuser\SvgStorage;

abstract class AbstractSvg extends TestCase
{
    protected DOMDocument $dom;

    protected SvgStorage $storage;

    protected function setUp(): void
    {
        $this->dom = new DOMDocument();
        $this->storage = new SvgStorage();
    }

    protected function assertsForSvgSprite($svgSpriteNode): void
    {
        $this->assertNotNull($svgSpriteNode);
        /** @var DOMElement $child */
        foreach ($svgSpriteNode->childNodes as $child) {
            $this->assertTrue(is_a($child, DOMElement::class));
            $this->assertSame($child->tagName, 'symbol');
            $this->assertTrue($child->hasAttribute('id'));
        }
    }

    /**
     */
    protected function assertsForSvgUse(
        $svgUseNode,
        string $id,
        string $overwriteClass = '',
        string $classFromFile = 'svg-first__class'
    ): void {
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
}
