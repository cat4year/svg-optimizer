<?php

declare(strict_types=1);

namespace SvgReuser\Manager;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use DOMXPath;
use SvgReuser\SvgException;

class SvgDomManager
{
    public function removeUnusedSymbols(DomNode $sprite, array $ids): void
    {
        /** @var DOMElement $symbol */
        foreach ($sprite->childNodes as $symbol) {
            if (!array_key_exists($symbol->getAttribute('id'), $ids)) {
                $sprite->removeChild($symbol);
            }
        }
    }

    public function getXpathElementById(string $id, DOMDocument $document): DOMNode|null
    {
        $xpath = new DOMXPath($document);

        return $xpath->query("//*[@id='$id']")->item(0);
    }

    /**
     * @throws SvgException
     */
    public function changeSymbolToSvg(DOMDocument $document, DOMElement $symbol): DOMElement
    {
        $svg = $this->createSvg($document);
        $this->setAttributesForSvgFromSymbol($svg, $symbol);

        $use = $this->createElement($svg->ownerDocument, 'use');
        $this->setAttributesForUseFromSymbol($use, $symbol);
        $svg->appendChild($use);

        return $svg;
    }

    /**
     * @throws SvgException
     */
    public function createSvg(DOMDocument $document): DOMElement
    {
        return $this->createElement($document, 'svg', ns: 'http://www.w3.org/2000/svg');
    }

    /**
     * @throws SvgException
     */
    public function createElement(DOMDocument $document, string $name, string $value = '', $ns = ''): DOMElement
    {
        try {
            if (!empty($ns)) {
                $element = $document->createElementNS($ns, $name, $value);
            } else {
                $element = $document->createElement($name, $value);
            }

            if (false === $element) {
                throw new DOMException(sprintf('Element "%s" was not created', $name));
            }

            return $element;
        } catch (DOMException $e) {
            throw new SvgException($e->getMessage());
        }
    }

    private function setAttributesForSvgFromSymbol(DOMElement $svg, DOMElement $symbol): void
    {
        foreach ($symbol->attributes as $attribute) {
            if ($attribute->nodeName === 'id') {
                continue;
            }

            $svg->setAttribute($attribute->nodeName, $attribute->nodeValue);
        }
    }

    private function setAttributesForUseFromSymbol(DOMElement $use, DOMElement $symbol): void
    {
        $use->setAttribute('href', sprintf('#%s', $symbol->getAttribute('id')));
        $use->setAttribute('x', '0');
        $use->setAttribute('y', '0');
    }

    public function appendChildNodeFromOtherDocument(
        DOMNode $targetElement,
        DOMNode $otherDocumentElement,
    ): DOMNode
    {
        return $targetElement->appendChild($targetElement->ownerDocument->importNode($otherDocumentElement, true));
    }

    public function loadCleanXml(DOMDocument $document, string $xmlContent): DOMDocument
    {
        $document->formatOutput = true;
        $document->preserveWhiteSpace = false;
        $document->validateOnParse = true;
        libxml_use_internal_errors(true);
        $document->loadXML($xmlContent);
        libxml_clear_errors();

        return $document;
    }
}