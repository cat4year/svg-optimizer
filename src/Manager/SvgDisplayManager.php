<?php

declare(strict_types=1);

namespace SvgReuser\Manager;

use DOMDocument;
use DOMElement;
use SvgReuser\SvgException;

final readonly class SvgDisplayManager
{
    private DOMDocument $svgDom;

    public function __construct(private SvgDomManager $svgDomManager)
    {
        $this->svgDom = new DOMDocument();
    }

    /**
     * @throws SvgException
     */
    public function buildSvgForShow(string $id, DOMDocument $spriteDom): DOMElement
    {
        $symbol = $this->svgDomManager->getXpathElementById($id, $spriteDom);

        if ($symbol === null || !is_a($symbol, DOMElement::class)) {
            throw new SvgException("Symbol with id $id not found");
        }

        return $this->svgDomManager->changeSymbolToSvg($this->svgDom, $symbol);
    }

    public function builtSvg(DOMElement $svg, string $class): string
    {
        $correctedSvg = $this->getElementWithClassOverwritten($svg, $class);

        return $correctedSvg->ownerDocument->saveXML($svg);
    }

    public function getElementWithClassOverwritten(DOMElement $element, string $class = ''): DOMElement
    {
        if ($class !== '') {
            $element->setAttribute('class', $class);
        }

        return $element;
    }
}
