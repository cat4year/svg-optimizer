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
use SvgReuser\Manager\FileManager;
use SvgReuser\Manager\SvgDomManager;
use SvgReuser\Manager\SvgMergeManager;

class SvgSpriteBuilder
{
    private Sanitizer $sanitizer;
    private FileManager $fileManager;
    private SvgDomManager $svgDomManger;
    private int $anonymousSymbolCounter = 0;
    private array $allIds = [];

    public function __construct(
        private readonly string $spriteName = 'sprite.svg',
        private array $definitionIdOrderedList = [],
        private string $symbolPrefix = 'symbol',
        private string $oldSpritePath = '',
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

        if ($this->oldSpritePath !== '') {
            $svgSprite = $this->mergeWithOldSprite($svgSprite);
        }

        $this->fileManager->saveToFile($pathTo, $svgSprite);
    }

    /**
     * @param array<int, string> $paths
     *
     * @throws DOMException
     * @throws SvgException
     */
    public function buildSpriteFromPaths(array $paths, string $pathTo): void
    {
        $svgSprite = $this->makeSvgSpriteFromFiles($paths, static function (string|int $key, $value, string &$fileContent): void {
            if (! is_numeric($key)) {
                $doc = new DOMDocument;
                $doc->loadXML($fileContent);
                $svg = $doc->getElementsByTagName('svg')->item(0);
                if ($svg !== null) {
                    $svg->setAttribute('id', $key);
                }
            }

            $fileContent = $svg->ownerDocument->saveXML($svg) ?: '';
        });

        if ($this->oldSpritePath !== '') {
            $svgSprite = $this->mergeWithOldSprite($svgSprite);
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

        if ($this->oldSpritePath !== '') {
            $svgSprite = $this->mergeWithOldSprite($svgSprite);
        }

        $this->fileManager->saveToFile($pathTo, $svgSprite);
    }

    /**
     * @throws SvgException
     */
    private function mergeWithOldSprite(string $svgSpriteContent): string
    {
        $oldSprite = $this->fileManager->getFromFile($this->oldSpritePath);

        $this->sanitize($oldSprite);

        $resultSprite = (new SvgMergeManager($oldSprite, $svgSpriteContent))->mergeSprites();

        return $this->sanitize($resultSprite);
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
        libxml_use_internal_errors(true);
        if ($xml) {
            $fromDocument->loadXML($content);
        } else {
            $fromDocument->loadHTML($content);
        }
        libxml_clear_errors();

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
        foreach ($svgNodes as $svgNode) {
            if (! $svgNode instanceof DOMElement) {
                continue;
            }
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
            if ($child instanceof DOMElement) {
                if ($this->checkAndAppendChild($symbol, $child) === false) {
                    return false;
                }
            } else {
                $this->svgDomManger->appendChildNodeFromOtherDocument($symbol, $child);
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
                DefinitionIdentificationEnum::ID => $this->getCorrectIdIfExist($svg),
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

    private function getCorrectIdIfExist(DOMElement $svg): string
    {
        if ($svg->hasAttribute('id')) {
            $nativeId = $svg->getAttribute('id');
            if (str_starts_with($nativeId, $this->symbolPrefix)) {
                $symbolNumber = (int) str_replace($this->symbolPrefix . '-', '', $nativeId);
                if ($symbolNumber > $this->anonymousSymbolCounter) {
                    $this->anonymousSymbolCounter = $symbolNumber;
                }
            }
        }

        return '';
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
    private function makeSvgSpriteFromFiles(array $filesPaths, ?callable $resolveId = null): string
    {
        $this->resetBeforeMakeSprite();
        $resultSvgSpriteNode = $this->svgDomManger->createSvg(new DOMDocument());

        foreach ($filesPaths as $key => $svgFilePath) {
            if (basename($svgFilePath) === $this->spriteName) {
                continue;
            }

            $fileContent = $this->fileManager->getFromFile($svgFilePath);

            if ($resolveId !== null) {
                $resolveId($key, $svgFilePath, $fileContent);
            }

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