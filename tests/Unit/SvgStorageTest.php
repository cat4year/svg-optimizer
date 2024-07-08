<?php

declare(strict_types=1);

namespace SvgReuser\Tests\Unit;

use DOMElement;
use SvgReuser\SvgException;

class SvgStorageTest extends AbstractSvg
{
    public function testIsValidLoadSprite(): void
    {
        try {
            $this->storage->loadSprite(dirname(__DIR__) . '/resources/images/sprite.svg');
            $this->assertTrue(true);
        } catch (SvgException $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testIsValidSpriteStructure(): void
    {
        $this->storage->loadSprite(dirname(__DIR__) . '/resources/images/sprite.svg');

        ob_start();
        $this->storage->showSprite(false);
        $content = ob_get_clean();

        $this->dom->preserveWhiteSpace = false;
        $this->dom->validateOnParse = true;
        $this->dom->loadXML($content);

        $svgNode = $this->dom->getElementsByTagName('svg')->item(0);
        $this->assertsForSvgSprite($svgNode);
    }

    public function testIsValidSpriteClassOverwritten(): void
    {
        $this->storage->loadSprite(dirname(__DIR__) . '/resources/images/sprite.svg');

        ob_start();
        $this->storage->showSprite(false, 'd-none');
        $content = ob_get_clean();

        $this->dom->preserveWhiteSpace = false;
        $this->dom->validateOnParse = true;
        $this->dom->loadXML($content);

        /** @var DOMElement $svgNode */
        $svgNode = $this->dom->getElementsByTagName('svg')->item(0);
        $this->assertSame($svgNode->getAttribute('class'), 'd-none');
    }

    public function testShowOneSvgOnlyById(): void
    {
        $this->storage->loadSprite(dirname(__DIR__) . '/resources/images/sprite.svg');
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
        $this->storage->loadSprite(dirname(__DIR__) . '/resources/images/sprite.svg');
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
        $this->storage->loadSprite(dirname(__DIR__) . '/resources/images/sprite.svg');
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
        $this->storage->loadSprite(dirname(__DIR__) . '/resources/images/sprite.svg');
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
