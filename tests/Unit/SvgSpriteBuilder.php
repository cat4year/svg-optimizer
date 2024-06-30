<?php

declare(strict_types=1);

namespace SvgReuser\Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMNode;
use PHPUnit\Framework\TestCase;
use SvgReuser\SvgStorage;

class SvgSpriteBuilder extends TestCase
{
    private DOMDocument $dom;
    private SvgStorage $storage;

    protected function setUp(): void
    {
        $this->dom = new DOMDocument();
        $this->storage = new SvgStorage();
    }
}
