<?php

declare(strict_types=1);

namespace Cat4year\SvgReuser;

use DOMDocument;
use DOMElement;
use DOMNode;
use ErrorException;
use Cat4year\SvgReuser\Manager\FileManager;
use Cat4year\SvgReuser\Manager\SvgDisplayManager;
use Cat4year\SvgReuser\Manager\SvgDomManager;

final class SvgStorage
{
    private DOMElement $sprite;
    private FileManager $fileManager;
    private DOMDocument $spriteDocument;
    private SvgDomManager $svgDomManager;
    private SvgDisplayManager $svgDisplayManager;
    private array $ids = [];

    public function __construct()
    {
        $this->fileManager = new FileManager();
        $this->spriteDocument = new DOMDocument();
        $this->svgDomManager = new SvgDomManager();
        $this->svgDisplayManager = new SvgDisplayManager($this->svgDomManager);
    }

    /**
     * @throws SvgException
     * @throws ErrorException
     */
    public function loadSprite(string $spriteFilePath = 'sprite.svg'): void
    {
        $svg = $this->fileManager->getFromFile($spriteFilePath);
        $this->spriteDocument->validateOnParse = true;
        $this->spriteDocument->preserveWhiteSpace = false;
        libxml_use_internal_errors(true);
        $this->spriteDocument->loadXML($svg);
        libxml_clear_errors();
        $spriteNode = $this->spriteDocument->getElementsByTagName('svg')->item(0);
        if (null === $spriteNode || $spriteNode->nodeType !== XML_ELEMENT_NODE) {
            throw new SvgException('Loaded sprite incorrect');
        }
        /** @var DOMElement $spriteNode */
        $this->sprite = $spriteNode;
    }

    public function isLoadedSprite(): bool
    {
        return isset($this->sprite);
    }

    /**
     * @return array<int, string>
     */
    public function getListAllSymbolIds(): array
    {
        $resultIds = [];
        /** @var DOMElement $symbol */
        $symbols = $this->sprite->childNodes;

        foreach ($symbols as $symbol) {
            $symbolId = $symbol->getAttribute('id');
            if (is_string($symbolId)) {
                $resultIds[] = $symbolId;
            }
        }

        return $resultIds;
    }

    /**
     * @return array<int, int>
     */
    public function getUsedListSymbolIds(): array
    {
        return $this->ids;
    }

    /**
     * @throws SvgException
     */
    public function showSprite(bool $onlyUsed = true, string $class = ''): void
    {
        echo $this->getSprite($onlyUsed, $class);
    }

    /**
     * @throws SvgException
     */
    public function getSprite(bool $onlyUsed = true, string $class = ''): string
    {
        if (! isset($this->sprite)) {
            throw new SvgException('Sprite is not loaded');
        }

        if ($onlyUsed === true) {
            $this->svgDomManager->removeUnusedSymbols($this->sprite, $this->ids);
        }

        $this->svgDisplayManager->getElementWithClassOverwritten($this->sprite, $class);

        return $this->spriteDocument->saveXML($this->sprite);
    }

    /**
     * @throws SvgException
     */
    public function showUseSvg(string $id, string $class = ''): void
    {
        echo $this->getUseSvg($id, $class);
    }

    /**
     * @throws SvgException
     */
    public function getUseSvg(string $id, string $class = ''): string
    {
        $this->makeUseSvg($id);

        return $this->svgDisplayManager->builtSvg($this->ids[$id], $class);
    }

    /**
     * @throws SvgException
     */
    public function makeUseSvg($id): void
    {
        if (! isset($this->sprite)) {
            throw new SvgException('Sprite is not loaded');
        }

        if (! array_key_exists($id, $this->ids)) {
            $this->ids[$id] = $this->svgDisplayManager->buildSvgForShow($id, $this->spriteDocument);
        }
    }

    /**
     * @throws SvgException
     */
    public function getCompleteSvg(string $id): string
    {
        $completeSvg = $this->buildCompleteSvg($id, $this->spriteDocument);

        return $completeSvg->ownerDocument->saveXML($completeSvg);
    }

    /**
     * @throws SvgException
     */
    public function buildCompleteSvg(string $id, DOMDocument $spriteDom): DOMElement
    {
        if (! isset($this->sprite)) {
            throw new SvgException('Sprite is not loaded');
        }

        $symbol = $this->svgDomManager->getXpathElementById($id, $spriteDom);

        if ($symbol === null || ! is_a($symbol, DOMElement::class)) {
            throw new SvgException("Symbol with id $id not found");
        }

        return $this->svgDomManager->changeSymbolToCompleteSvg(new DOMDocument(), $symbol);
    }

    public function getSpriteNode(): DOMNode
    {
        return $this->sprite;
    }
}
