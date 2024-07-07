<?php

declare(strict_types=1);

namespace SvgReuser;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use DOMNodeList;
use enshrined\svgSanitize\Sanitizer;
use Exception;
use SvgReuser\Manager\SvgDomManager;

class SvgSpriteBuilder
{
    private Sanitizer $sanitizer;
    private FileManager $fileManager;
    private SvgDomManager $svgDomManger;
    private int $anonymousSymbolCounter = 0;
    private array $allIds = [];

    public function __construct(
        private readonly string $spriteName = 'sprite.svg',
        protected array $definitionIdOrderedList = [],
        protected string $symbolPrefix = 'symbol',
        bool $shouldMinify = true,
    )
    {
        $this->fileManager = new FileManager();
        $this->sanitizer = new Sanitizer();
        $this->svgDomManger = new SvgDomManager();
        $this->sanitizer->minify($shouldMinify);
    }

    /**
     * @throws DOMException
     * @throws SvgException
     */
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
     * @throws SvgException
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
        $resultSvgSpriteNode = $this->svgDomManger->createSvg(new DOMDocument());

        $this->appendAllCorrectSvgFromFileToSprite($html, $resultSvgSpriteNode, false);

        $resultSvgText = $resultSvgSpriteNode->ownerDocument->saveXML($resultSvgSpriteNode);

        return $this->sanitize((string) $resultSvgText);
    }

    /**
     * @throws DOMException
     * @throws SvgException
     */
    private function appendAllCorrectSvgFromFileToSprite(string $content, DOMNode $spriteNode, bool $xml = true): void
    {
        $fromDocument = new DOMDocument();
        $fromDocument->formatOutput = true;
        $fromDocument->preserveWhiteSpace = false;
        $fromDocument->validateOnParse = true;
        if ($xml) {
            libxml_use_internal_errors(true);
            $fromDocument->loadXML($content);
            libxml_clear_errors();
        } else {
            $fromDocument->loadHTML($content);
        }

        $svgNodes = $fromDocument->getElementsByTagName('svg');
        $this->appendCorrectSvgFromSymbolToDocument($svgNodes, $spriteNode);
    }

    /**
     * This method reload svg, because if u use loadHtml - u can get DOMText on svg child
     * reload svg as clean xml fix this problem
     *
     * @throws DOMException
     * @throws SvgException
     */
    private function appendCorrectSvgFromSymbolToDocument(DOMNodeList $svgNodes, DOMNode $targetSpriteNode): void
    {
        /** @var DOMElement $svgNode */
        foreach ($svgNodes as $svgNode) {
            $svgContent = $this->getValidSvg($svgNode);
            $correctSvgNodeDocument = new DOMDocument();
            $this->svgDomManger->loadCleanXml($correctSvgNodeDocument, $svgContent);
            $correctSvgNode = $correctSvgNodeDocument->documentElement;

            $symbol = $this->changeSvgToSymbol($correctSvgNode);
            if ($symbol !== false) {
                $this->svgDomManger->appendChildNodeFromOtherDocument($targetSpriteNode, $symbol);
            }
        }
    }

    /**
     * @throws SvgException
     */
    private function getValidSvg($svgNode): string
    {
        $svgString = $svgNode->ownerDocument->saveXML($svgNode);
        $this->sanitize($svgString);

        return $svgString;
    }

    /**
     * @throws DOMException
     */
    private function changeSvgToSymbol(DOMElement $svg): false|DOMElement
    {
        $symbolDocument = new DOMDocument();
        $symbol = $symbolDocument->createElement('symbol');

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
        $resultSvgSpriteNode = $this->svgDomManger->createSvg(new DOMDocument());

        foreach ($filesPaths as $svgFilePath) {
            if (basename($svgFilePath) === $this->spriteName) {
                continue;
            }
            $fileContent = $this->fileManager->getFromFile($svgFilePath);

            $this->appendAllCorrectSvgFromFileToSprite($fileContent, $resultSvgSpriteNode);
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

        $this->svgDomManger->appendChildNodeFromOtherDocument($symbol, $childElement);

        return true;
    }
}