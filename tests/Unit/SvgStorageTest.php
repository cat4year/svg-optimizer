<?php

declare(strict_types=1);

namespace SvgReuser\Tests\Unit;

use DOMDocument;
use DOMNode;
use PHPUnit\Framework\TestCase;
use SvgReuser\SvgStorage;

class SvgStorageTest extends TestCase
{
    private DOMDocument $dom;
    private SvgStorage $storage;

    protected function setUp(): void
    {
        $this->dom = new DOMDocument(encoding: 'UTF-8');
        $this->storage = new SvgStorage();

        $svgFirstContent = $this->getSvgContent('svg-first.svg');
        $svgSecondContent = $this->getSvgContent('svg-second.svg');

        $this->storage->add('svg-first', $svgFirstContent);
        $this->storage->add('svg-second', $svgSecondContent);
    }

    protected function getSvgContent($filename): string
    {
        $filePath = __DIR__ . "/../resources/images/{$filename}";

        return file_get_contents($filePath);
    }

    public function testIsValidSvgSprite(): void
    {
        $sprite = $this->storage->getSprite('d-none');
        $this->dom->validateOnParse = true;
        $this->dom->loadXML('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24">
    <symbol id="icon-active" viewBox="0 0 24 24">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2z"/>
    </symbol>
    <symbol id="icon-inactive" viewBox="0 0 24 24">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2z"/>
    </symbol>
</svg>');

        echo "<pre>";
        print_r($this->dom->save('test'));
        echo "</pre>";
        if ($this->dom->validate()) {
            echo 'VALIDEN';
        } else {
            echo 'NE VALIDEN';
            throw new \Exception('ne validen!!');
        }

        $this->assertTrue($this->dom->validate());
    }

    public function testSvgSprite(): void
    {
        $sprite = $this->storage->getSprite('d-none');

        $this->dom->loadXML($sprite);

        /** @var DOMNode $svgNode */
        $svgNode = $this->dom->getElementsByTagName('svg')->item(0);
        /** @var DOMNode $symbol */
        $hasNonSymbol = false;
        foreach($svgNode as $symbol){
            if ($symbol->nodeName !== 'symbol') {
                $hasNonSymbol = true;
                break;
            }
        }

        $this->assertFalse($hasNonSymbol);
        $this->assertEquals('d-none', $svgNode->className);
    }

    public function testIsValidSvgSpriteSymbol(): void
    {
        $sprite = $this->storage->getSprite('d-none');

        $this->dom->loadXML($sprite);

        /** @var DOMNode $svgNode */
        $svgNode = $this->dom->getElementsByTagName('svg')->item(0);
        $firstSymbol = $svgNode->firstChild;

        $this->assertNotNull($firstSymbol);
    }

    public function testSvgSpriteSymbol(): void
    {
        $sprite = $this->storage->getSprite('d-none');

        $this->dom->loadXML($sprite);

        /** @var DOMNode $svgNode */
        $svgNode = $this->dom->getElementsByTagName('svg')->item(0);

        $firstSymbol = $svgNode->firstChild;
        $this->assertEquals('symbol', $firstSymbol);
        $this->assertEquals('svg-first', $firstSymbol->getAttribute('svg-first'));
    }

    public function testSvgUseHref(): void
    {
        $someSvgUseCode = $this->storage->get('svg-first');

        $this->dom->loadXML($someSvgUseCode);
        /** @var DOMNode $svgNode */
        $svgNode = $this->dom->getElementsByTagName('svg')->item(0);
        $this->assertNotNull($svgNode);
        /** @var DOMNode $useNode */
        $useNode = $svgNode->getElementsByTagName("use")->item(0);
        $this->assertNotNull($useNode);

        $hrefValue = $useNode->getAttribute('href');

        $this->assertEquals('#svg-first', $hrefValue);
        $this->assertEquals('svg-first__class', $svgNode->className);
    }
}
