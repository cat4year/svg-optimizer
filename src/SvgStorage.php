<?php

declare(strict_types=1);

namespace SvgReuser;

use DOMDocument;
use DOMElement;
use DOMNode;
use SvgReuser\Manager\SvgDisplayManager;
use SvgReuser\Manager\SvgDomManager;

class SvgStorage
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

    public function showSprite(bool $onlyUsed = true, string $class = ''): void
    {
        if ($onlyUsed === true) {
            $this->svgDomManager->removeUnusedSymbols($this->sprite, $this->ids);
        }

        $this->svgDisplayManager->getElementWithClassOverwritten($this->sprite, $class);

        echo $this->spriteDocument->saveXML();
    }

    /**
     * @throws SvgException
     */
    public function showSvg(string $id, string $class = ''): void
    {
        if (!array_key_exists($id, $this->ids)) {
            $this->ids[$id] = $this->svgDisplayManager->buildSvgForShow($id, $this->spriteDocument);
        }

        $this->svgDisplayManager->showBuiltSvg($this->ids[$id], $class);
    }

    public function getSprite(): DOMNode
    {
        return $this->sprite;
    }
}