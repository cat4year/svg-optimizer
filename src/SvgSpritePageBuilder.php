<?php

declare(strict_types=1);

namespace SvgReuser;

use DOMDocument;
use DOMElement;
use DOMException;
use SvgReuser\Manager\FileManager;

class SvgSpritePageBuilder
{
    private FileManager $fileManager;

    private DOMDocument $dom;

    private SvgStorage $storage;

    public function __construct(
        private readonly string $spriteName = 'sprite.svg',
    )
    {
        $this->fileManager = new FileManager();
        $this->dom = new DOMDocument();
        $this->storage = new SvgStorage();
    }

    /**
     * @throws SvgException
     */
    public function buildSpritePage(string $spritePath, string $spritePagePath = ''): void
    {
        try {
            $svgSpritePage = $this->makeSpritePage($spritePath);
        } catch (DOMException $e) {
            throw new SvgException('Cannot make sprite page', 0, $e);
        }

        if (empty($spritePagePath)) {
            $spritePagePath = dirname($spritePath) . '/' . basename($this->spriteName) . '.html';
        }

        $this->fileManager->saveToFile($spritePagePath, $svgSpritePage);
    }

    /**
     * @throws DOMException
     * @throws SvgException
     */
    private function makeSpritePage(string $spritePath): string
    {
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = true;

        $this->storage->loadSprite($spritePath);

        $body = $this->makeBody();
        $this->addStyles($body);

        $svgElements = $this->makeSvgsElements();
        $table = $this->makeTable($body);
        $this->fillTableWithSvg($svgElements, $table);

        $this->showSvgSprite();

        return $this->dom->saveHTML();
    }

    private function getSymbolsIds(): array
    {
        /** @var DOMElement $symbol */
        $result = [];
        $sprite = $this->storage->getSpriteNode();
        foreach ($sprite->childNodes as $symbol) {
            $result[] = $symbol->getAttribute('id');
        }

        return $result;
    }

    /**
     * @return DOMDocument[]
     * @throws SvgException
     */
    private function makeSvgsElements(): array
    {
        $symbolsIds = $this->getSymbolsIds();

        $result = [];
        foreach ($symbolsIds as $id) {
            ob_start();
            $this->storage->showUseSvg($id);
            $svg = ob_get_clean();
            $svgDocument = new DOMDocument();
            $svgDocument->loadHTML($svg);
            $svgElement = $svgDocument->getElementsByTagName('svg')->item(0);
            if ($svgElement === null) {
                continue;
            }
            $result[$id] = $this->dom->importNode($svgElement, true);
        }

        return $result;
    }

    private function showSvgSprite(): void
    {
        ob_start();
        $this->storage->showSprite();
        $sprite = ob_get_clean();
        $spriteElement = new DOMDocument();
        $spriteElement->loadXml($sprite);
        $this->dom->appendChild($this->dom->importNode($spriteElement->documentElement, true));
    }

    /**
     * @throws DOMException
     */
    private function makeTable(DOMElement $body): DOMElement
    {
        $table = $this->dom->createElement('table');
        $body->appendChild($table);

        $headers = ['ID', 'SVG'];
        $row = $this->dom->createElement('tr');
        foreach ($headers as $header) {
            $th = $this->dom->createElement('th', $header);
            $row->appendChild($th);
        }
        $table->appendChild($row);

        return $table;
    }

    /**
     * @throws DOMException
     */
    private function fillTableWithSvg(array $svgElements, DOMElement $table): void
    {
        foreach ($svgElements as $svgId => $svg) {
            $row = $this->dom->createElement('tr');
            $tdId = $this->dom->createElement('td', $svgId);
            $tdSvg = $this->dom->createElement('td');
            $tdSvg->appendChild($svg);
            $row->appendChild($tdId);
            $row->appendChild($tdSvg);
            $table->appendChild($row);
        }
    }

    /**
     * @throws DOMException
     */
    private function addStyles(false|DOMElement $body): void
    {
        $style = $this->dom->createElement('style');
        $style->setAttribute('type', 'text/css');
        $text = $this->dom->createTextNode('
        table { border: 1px solid black; text-align: center; min-width: 480px; }
        tr td:last-child {
           background-image: linear-gradient(black, white);
        }
        td { border: 1px solid black; min-width: 80px; width: 50%}
        th { border: 1px solid black; }
        ');
        $style->appendChild($text);
        $body->appendChild($style);
    }

    /**
     * @throws DOMException
     */
    private function makeBody(): DOMElement
    {
        $body = $this->dom->createElement('body');
        $this->dom->appendChild($body);

        return $body;
    }
}