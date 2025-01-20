<?php

declare(strict_types=1);

namespace SvgReuser\Manager;

use DOMElement;
use SvgReuser\SvgException;
use DOMDocument;

final class SvgMergeManager
{
    private DOMElement $oldSprite;
    private DOMElement $sprite;
    private SvgDomManager $svgDomManager;
    private int $lastOrderNumber = 0;
    private string $symbolPrefix = 'symbol';

    public function __construct(
        private readonly string $oldSpriteContent,
        private readonly string $spriteContent,
    ) {
        $this->svgDomManager = new SvgDomManager();
    }

    /**
     * @throws SvgException
     */
    private function loadSprites(): void
    {
        $this->oldSprite = $this->svgDomManager->loadCleanSvg($this->oldSpriteContent);
        $this->sprite = $this->svgDomManager->loadCleanSvg($this->spriteContent);
    }

    /**
     * @throws SvgException
     */
    public function mergeSprites(): string
    {
        $this->loadSprites();
        $this->findAndSetLastOrderNumber();

        $spriteSymbols = $this->sprite->getElementsByTagName('symbol');
        foreach ($spriteSymbols as $symbol) {
            if ($this->isExistInSprite($symbol, $this->oldSprite)) {
                continue;
            }

            $this->adaptSymbolForOldSprite($symbol);

            $this->svgDomManager->appendChildNodeFromOtherDocument($this->oldSprite, $symbol);
        }

        return $this->oldSprite->ownerDocument->saveXml();
    }

    private function isExistInSprite(DOMElement $symbol, DOMElement $oldSprite): bool
    {
        $tempDocumentForCompare = new DOMDocument();
        $symbolCopy = $tempDocumentForCompare->importNode($symbol, true);

        $oldSpriteSymbols = $oldSprite->getElementsByTagName('symbol');
        foreach ($oldSpriteSymbols as $oldSymbol) {
            if ($oldSymbol->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $oldSymbolCopy = $tempDocumentForCompare->importNode($oldSymbol, true);
            if ($this->svgDomManager->isEqualElements($oldSymbolCopy, $symbolCopy)) {
                return true;
            }
        }

        return false;
    }

    private function findAndSetLastOrderNumber(): void
    {
        $symbols = $this->oldSprite->getElementsByTagName('symbol');
        /** @var DOMElement $symbol */
        foreach ($symbols as $symbol) {
            if ($symbol->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $id = $symbol->getAttribute('id');
            if (str_starts_with($id, $this->symbolPrefix)) {
                $symbolNumber = (int) str_replace($this->symbolPrefix . '-', '', $id);
                if ($symbolNumber > $this->lastOrderNumber) {
                    $this->lastOrderNumber = $symbolNumber;
                }
            }
        }
    }

    private function adaptSymbolForOldSprite(DOMElement $symbol): void
    {
        $id = $symbol->getAttribute('id');

        if (str_starts_with($id, $this->symbolPrefix)) {
            $newOrdinalId = sprintf('%s-%d', $this->symbolPrefix, ++$this->lastOrderNumber);
            $symbol->setAttribute('id', $newOrdinalId);
        }
    }
}
