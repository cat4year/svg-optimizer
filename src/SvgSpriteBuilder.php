<?php

declare(strict_types=1);

namespace SvgReuser;

use DOMDocument;
use DOMElement;
use DOMException;
use enshrined\svgSanitize\Sanitizer;
use Exception;

class SvgSpriteBuilder
{
    private Sanitizer $sanitizer;
    private FileManager $fileManager;
    protected DomDocument $dom;
    private int $anonymousSymbolCounter = 0;
    private array $allIds = [];

    public function __construct(
        private readonly string $spriteName = 'sprite.svg',
        protected array $definitionIdOrderedList = [],
        protected string $symbolPrefix = 'symbol-',
    )
    {
        $this->fileManager = new FileManager();
        $this->dom = new DomDocument();
        $this->sanitizer = new Sanitizer();
        //$this->sanitizer->minify(true);
    }

    public function buildSpriteFromDirectory(string $path, string $pathTo = ''): void
    {
        $svgFiles = $this->fileManager->collectSvgFiles($path);
        $svgSprite = $this->makeSvgSpriteFromFiles($svgFiles);

        if (empty($pathTo)) {
            $pathTo = $path . '/' . $this->spriteName;
        }

        $this->fileManager->saveToFile($pathTo, $svgSprite);
    }

    /**
     * @throws Exception
     */
    public function buildSpriteFromFile(string $pathFrom, string $pathTo = ''): void
    {
        $fileContent = $this->fileManager->getFromFile($pathFrom);

        $svgSprite = $this->makeSvgSpriteFromHtml($fileContent);

        if (empty($pathTo)) {
            $pathTo = dirname($pathFrom) . '/' . $this->spriteName;
        }

        $this->fileManager->saveToFile($pathTo, $svgSprite);
    }

    /**
     * @throws Exception
     */
    private function sanitize(string $svg): string
    {
        $cleanSvg = $this->sanitizer->sanitize($svg);

        if (false === $cleanSvg) {
            throw new SvgException('Not valid svg file');
        }

        return $cleanSvg;
    }

    /**
     * @throws DOMException
     * @throws Exception
     */
    private function makeSvgSpriteFromHtml(string $html): string
    {
        $this->resetBeforeMakeSprite();

        libxml_use_internal_errors(true);
        $this->dom->loadHTML($html);
        libxml_clear_errors();

        $resultSvgSpriteNode = $this->dom->createElementNS('http://www.w3.org/2000/svg', 'svg');

        $svgNodes = $this->dom->getElementsByTagName('svg');
        /** @var DOMElement $svgNode */
        foreach ($svgNodes as $svgNode) {
            $svgString = $svgNode->ownerDocument->saveXML($svgNode);
            $this->sanitize($svgString);

            $symbol = $this->changeSvgToSymbol($svgNode);
            if ($symbol !== false) {
                $resultSvgSpriteNode->appendChild($this->dom->importNode($symbol, true));
            }
        }

        $resultSvgText = $resultSvgSpriteNode->ownerDocument->saveXML($resultSvgSpriteNode);

        return $this->sanitize((string) $resultSvgText);
    }

    /**
     * @throws DOMException
     */
    private function changeSvgToSymbol(DOMElement $svg): false|DOMElement
    {
        $symbol = $svg->ownerDocument->createElement('symbol');

        $id = $this->makeSymbolId($svg);

        if (in_array($id, $this->allIds, true)) {
            return false;
        }
        $this->allIds[] = $id;
        $symbol->setAttribute('id', $id);

        foreach ($svg->attributes as $attribute) {
            $symbol->setAttribute($attribute->nodeName, $attribute->nodeValue);
        }

        foreach ($svg->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                //todo: maybe need more level children?
                while (($childElement = $child->nextElementSibling) !== null) {
                    if (false === $this->checkAndAppendChild($symbol, $childElement)) {
                        return false;
                    }
                }
                continue;
            }

            if (false === $this->checkAndAppendChild($symbol, $child)) {
                return false;
            }
        }

        return $symbol;
    }

    protected function makeSymbolId(DOMElement $svg): string
    {
        $definitionIdList = $this->definitionIdOrderedList;
        if (empty($definitionIdList)) {
            $definitionIdList = DefinitionIdentificationEnum::cases();
        }

        foreach ($definitionIdList as $definitionId) {
            $id = match ($definitionId) {
                DefinitionIdentificationEnum::ID => $svg->hasAttribute('id')
                    ? $svg->getAttribute('id')
                    : '',
                DefinitionIdentificationEnum::HASH => $this->hash($svg->ownerDocument->saveXML($svg)),
                DefinitionIdentificationEnum::SVG_CLASS => $svg->hasAttribute('class')
                    ? str_replace(' ', '_',  $svg->getAttribute('class'))
                    : '',
                DefinitionIdentificationEnum::ORDINAL => sprintf(
                    '%s-%d',
                    $this->symbolPrefix,
                    ++$this->anonymousSymbolCounter
                ),
                default => '',
            };

            if (!empty($id)) {
                return $id;
            }
        }

        return sprintf(
            '%s-%d',
            $this->symbolPrefix,
            ++$this->anonymousSymbolCounter
        );
    }

    protected function hash(string $input): string
    {
        $hash = hash('sha256', $input);

        return (string) preg_replace('/[^A-Za-z0-9]/', '', $hash);
    }

    /**
     * @throws DOMException
     * @throws Exception
     */
    private function makeSvgSpriteFromFiles(array $filesPaths): string
    {
        $this->resetBeforeMakeSprite();
        $resultSvgSpriteNode = $this->dom->createElementNS('http://www.w3.org/2000/svg', 'svg');

        foreach ($filesPaths as $svgFilePath) {
            if (basename($svgFilePath) === $this->spriteName) {
                continue;
            }
            $fileContent = $this->fileManager->getFromFile($svgFilePath);
            $tempDom = new DOMDocument();
            libxml_use_internal_errors(true);
            $tempDom->loadHTML($fileContent);
            libxml_clear_errors();

            $svgNodes = $tempDom->getElementsByTagName('svg');
            /** @var DOMElement $svgNode */
            foreach ($svgNodes as $svgNode) {
                $svgString = $svgNode->ownerDocument->saveXML($svgNode);
                $this->sanitize($svgString);

                $symbol = $this->changeSvgToSymbol($svgNode);
                if ($symbol !== false) {
                    $resultSvgSpriteNode->appendChild($this->dom->importNode($symbol, true));
                }
            }
        }

        $resultSvgText = $resultSvgSpriteNode->ownerDocument->saveXML($resultSvgSpriteNode);

        return $this->sanitize((string) $resultSvgText);
    }

    private function resetBeforeMakeSprite(): void
    {
        $this->anonymousSymbolCounter = 0;
        $this->allIds = [];
    }

    private function checkAndAppendChild(DOMElement $symbol, DOMElement $childElement): bool
    {
        if ($childElement->hasAttribute('id')) {
            $id = $childElement->getAttribute('id');
            if (in_array($id, $this->allIds, true)) {
                return false;
            }
            $this->allIds[] = $id;
        }

        $symbol->appendChild($childElement);

        return true;
    }
}