<?php

declare(strict_types=1);

namespace SvgReuser;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use DOMXPath;

class SvgStorage
{
    private DOMNode $sprite;
    private FileManager $fileManager;
    private DOMDocument $spriteDom;
    private DOMDocument $svgDom;
    private array $ids = [];

    public function __construct()
    {
        $this->fileManager = new FileManager();
        $this->spriteDom = new DOMDocument();
        $this->svgDom = new DOMDocument();
    }

    /**
     * @throws SvgException
     */
    public function loadSprite(string $spriteFilePath = 'sprite.svg'): void
    {
        $svg = $this->fileManager->getFromFile($spriteFilePath);
        $this->spriteDom->validateOnParse = true;
        $this->spriteDom->preserveWhiteSpace = false;
        libxml_use_internal_errors(true);
        $this->spriteDom->loadXML($svg);
        libxml_clear_errors();
        $spriteNode = $this->spriteDom->getElementsByTagName('svg')->item(0);
        if (null === $spriteNode) {
            throw new SvgException('Loaded sprite incorrect');
        }
        $this->sprite = $spriteNode;
    }

    public function showSprite(bool $onlyUsed = true): void
    {
        if ($onlyUsed === true) {
            $this->removeUnusedSymbols();
        }

        echo $this->spriteDom->saveXML();
    }

    private function removeUnusedSymbols(): void
    {
        /** @var DOMElement $symbol */
        foreach ($this->sprite->childNodes as $symbol) {
            if (!array_key_exists($symbol->getAttribute('id'), $this->ids)) {
                $this->sprite->removeChild($symbol);
            }
        }
    }

    /**
     * @param string $id
     * @param string $class
     * @throws DOMException
     * @throws SvgException
     */
    public function showSvg(string $id, string $class = ''): void
    {
        if (array_key_exists($id, $this->ids)) {
            $correctedSvg = $this->getCorrectedSvg($this->ids[$id], $class);
            echo $correctedSvg->ownerDocument->saveXML($this->ids[$id]);

            return;
        }

        $symbol = $this->getElementById($id, $this->spriteDom);
        if ($symbol === null) {
            throw new SvgException("Symbol with id $id not found");
        }

        $svg = $this->changeSymbolToSvg($symbol);

        $this->ids[$id] = $this->getCorrectedSvg($svg, $class);
        echo $this->ids[$id]->ownerDocument->saveXML($this->ids[$id]);
    }

    private function getCorrectedSvg(DOMNode $svg, string $class = ''): DOMNode
    {
        if ($class !== '') {
            $svg->setAttribute('class', $class);
        }

        return $svg;
    }

    private function getElementById(string $id, DOMDocument $document): DOMNode|null
    {
        $xpath = new DOMXPath($document);

        return $xpath->query("//*[@id='$id']")->item(0);
    }

    /**
     * @throws DOMException
     * @throws SvgException
     */
    private function changeSymbolToSvg(DOMElement $symbol): DOMElement
    {
        $svg = $this->svgDom->createElement('svg');
        if (false === $svg) {
            throw new SvgException('Can not change symbol to svg');
        }

        $svg->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        foreach ($symbol->attributes as $attribute) {
            if ($attribute->nodeName === 'id') {
                continue;
            }
            $svg->setAttribute($attribute->nodeName, $attribute->nodeValue);
        }

        $use = $svg->ownerDocument->createElement('use');
        $use->setAttribute('href', sprintf('#%s', $symbol->getAttribute('id')));
        $use->setAttribute('x', '0');
        $use->setAttribute('y', '0');
        $svg->appendChild($use);

        return $svg;
    }
}