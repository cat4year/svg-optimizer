<?php

declare(strict_types=1);

namespace Cat4year\SvgReuser\Manager;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use DOMXPath;
use Cat4year\SvgReuser\SvgException;

final class SvgDomManager
{
    /**
     * @throws SvgException
     */
    public function loadCleanSvg(string $content, ?DOMDocument $document = null): DOMElement
    {
        if (null === $document) {
            $document = new DOMDocument();
        }

        $document->validateOnParse = true;
        $document->preserveWhiteSpace = false;
        libxml_use_internal_errors(true);
        $document->loadXML($content);
        libxml_clear_errors();

        $spriteNode = $document->getElementsByTagName('svg')->item(0);
        if (null === $spriteNode || $spriteNode->nodeType !== XML_ELEMENT_NODE) {
            throw new SvgException('Loaded sprite incorrect');
        }

        /** @var DOMElement $spriteNode */
        return $spriteNode;
    }

    public function removeUnusedSymbols(DOMNode $sprite, array $ids): void
    {
        $symbolsToRemove = [];

        /** @var DOMElement $symbol */
        foreach ($sprite->childNodes as $symbol) {
            if (!array_key_exists($symbol->getAttribute('id'), $ids)) {
                $symbolsToRemove[] = $symbol;
            }
        }

        foreach ($symbolsToRemove as $symbol) {
            $sprite->removeChild($symbol);
        }
    }

    public function getXpathElementById(string $id, DOMDocument $document): ?DOMNode
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
    public function changeSymbolToCompleteSvg(DOMDocument $document, DOMElement $symbol): DOMElement
    {
        $svg = $this->createSvg($document);
        $this->setAttributesForCompleteSvgSymbol($svg, $symbol);

        foreach ($symbol->childNodes as $child) {
            $this->appendChildNodeFromOtherDocument($svg, $child);
        }

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

    private function setAttributesForCompleteSvgSymbol(DOMElement $svg, DOMElement $symbol): void
    {
        foreach ($symbol->attributes as $attribute) {
            if ($attribute->nodeName === 'id') {
                continue;
            }

            $svg->setAttribute($attribute->nodeName, $attribute->nodeValue);
        }
    }

    private function setAttributesForSvgFromSymbol(DOMElement $svg, DOMElement $symbol): void
    {
        foreach ($symbol->attributes as $attribute) {
            if ($attribute->nodeName !== 'class') {
                continue;
            }

            $svg->setAttribute($attribute->nodeName, $attribute->nodeValue);
        }
    }

    private function setAttributesForUseFromSymbol(DOMElement $use, DOMElement $symbol): void
    {
        $use->setAttribute('href', sprintf('#%s', $symbol->getAttribute('id')));
        $use->setAttribute('xlink:href', sprintf('#%s', $symbol->getAttribute('id')));
        $use->setAttribute('x', '0');
        $use->setAttribute('y', '0');
    }

    public function appendChildNodeFromOtherDocument(
        DOMNode $targetElement,
        DOMNode $otherDocumentElement,
    ): DOMNode {
        return $targetElement->appendChild($targetElement->ownerDocument->importNode($otherDocumentElement, true));
    }

    public function loadCleanXml(DOMDocument $document, string $xmlContent): DOMDocument
    {
        $document->formatOutput = true;
        $document->preserveWhiteSpace = false;
        $document->validateOnParse = true;
        libxml_use_internal_errors(true);
        $document->loadXML($xmlContent, LIBXML_NOBLANKS);
        $document->C14N(true);
        libxml_clear_errors();

        return $document;
    }

    public function isEqualElements(DOMElement $firstElement, DOMElement $secondElement): bool
    {
        if ($firstElement->childNodes->count() !== $secondElement->childNodes->count()) {
            return false;
        }

        return $this->cleanXmlElementStringForCompare($firstElement) === $this->cleanXmlElementStringForCompare($secondElement);
    }

    private function cleanXmlElementStringForCompare(DOMElement $element): string
    {
        /** @var DOMAttr $attribute */
        foreach ($element->attributes as $attribute) {
            $element->removeAttribute($attribute->name);
        }

        $element->removeAttributeNS('http://www.w3.org/2000/svg', $element->nodeName);
        $element->removeAttribute('xmlns');

        $elementString = $element->ownerDocument->saveXml($element);
        $elementString = str_replace('xmlns="http://www.w3.org/2000/svg"', '', $elementString);

        return preg_replace('/[\s\t\n\r]/u', '', $elementString);
    }

    public function findAndSetLastOrderNumber(DOMElement $svg, string $prefix, int &$lastNumber): int
    {
        $symbols = $svg->getElementsByTagName('symbol');
        /** @var DOMElement $symbol */
        foreach ($symbols as $symbol) {
            if ($symbol->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $id = $symbol->getAttribute('id');
            if (str_starts_with($id, $prefix)) {
                $symbolNumber = (int) str_replace($prefix . '-', '', $id);
                if ($symbolNumber > $lastNumber) {
                    $lastNumber = $symbolNumber;
                }
            }
        }

        return $lastNumber;
    }
}
