<?php

declare(strict_types=1);

namespace SvgReuser\Manager;

use DOMDocument;
use DOMElement;
use SvgReuser\SvgException;

readonly class SvgDisplayManager
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

    public function showBuiltSvg(DOMElement $svg, string $class): void
    {
        $correctedSvg = $this->getSvgWithClassOverwritten($svg, $class);
        echo $correctedSvg->ownerDocument->saveXML($svg);
    }

    private function getSvgWithClassOverwritten(DOMElement $svg, string $class = ''): DOMElement
    {
        if ($class !== '') {
            $svg->setAttribute('class', $class);
        }

        return $svg;
    }
}