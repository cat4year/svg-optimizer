<?php

declare(strict_types=1);

namespace Cat4year\SvgReuser\Tests\Unit;

use Cat4year\SvgReuser\DefinitionIdentificationEnum;
use Cat4year\SvgReuser\Sanitizer\InternalSanitizer;
use Cat4year\SvgReuser\SvgSpriteBuilder;
use Exception;

final class SvgSpriteBuilderTest extends AbstractSvg
{
    private SvgSpriteBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $sanitizer = new InternalSanitizer(true);

        $this->builder = new SvgSpriteBuilder(
            sanitizer: $sanitizer,
            definitionIdOrderedList: [
                DefinitionIdentificationEnum::ID,
                DefinitionIdentificationEnum::SVG_CLASS,
                DefinitionIdentificationEnum::ORDINAL,
                DefinitionIdentificationEnum::HASH,
            ]
        );
    }

    public function testIsValidBuildSpriteFromFile(): void
    {
        try {
            $this->builder->buildSpriteFromFile(
                dirname(__DIR__) . '/resources/build/from/dirty-svgs.html',
                dirname(__DIR__) . '/resources/build/result/sprite-from-file.html',
            );
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testIsValidBuiltSpriteStructureFromFile(): void
    {
        $spriteFromFilePath = dirname(__DIR__) . '/resources/build/result/sprite-from-file.svg';
        $this->builder->buildSpriteFromFile(
            dirname(__DIR__) . '/resources/build/from/dirty-svgs.html',
            $spriteFromFilePath
        );
        $this->storage->loadSprite($spriteFromFilePath);

        $content =  $this->storage->getSprite(false);

        $this->dom->preserveWhiteSpace = false;
        $this->dom->validateOnParse = true;
        $this->dom->loadXML($content);

        $svgNode = $this->dom->getElementsByTagName('svg')->item(0);
        $this->assertsForSvgSprite($svgNode);
    }

    public function testIsValidBuildSpriteFromDirectory(): void
    {
        try {
            $this->builder->buildSpriteFromDirectory(dirname(__DIR__) . '/resources/build/from');
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testIsValidBuiltSpriteStructureFromDirectory(): void
    {
        $spriteFromFilePath = dirname(__DIR__) . '/resources/build/result/sprite-from-directory.svg';
        $this->builder->buildSpriteFromDirectory(dirname(__DIR__) . '/resources/build/from/', $spriteFromFilePath);
        $this->storage->loadSprite($spriteFromFilePath);

        $content =  $this->storage->getSprite(false);

        $this->dom->preserveWhiteSpace = false;
        $this->dom->validateOnParse = true;
        $this->dom->loadXML($content);

        $svgNode = $this->dom->getElementsByTagName('svg')->item(0);

        $this->assertsForSvgSprite($svgNode);
    }

    public function testIsValidBuildSpriteFromPaths(): void
    {
        try {
            $this->builder->buildSpriteFromPaths(
                [
                    dirname(__DIR__) . '/resources/build/from/icons/arrow-down.svg',
                    dirname(__DIR__) . '/resources/build/from/icons/burger-menu.svg',
                    dirname(__DIR__) . '/resources/build/from/icons/checkbox.svg',
                    dirname(__DIR__) . '/resources/build/from/icons/icon-phone.svg',
                ],
                dirname(__DIR__) . '/resources/build/result/sprite-from-files-paths.svg',
            );
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testIsValidBuiltSpriteFromPaths(): void
    {
        $spriteFromFilePath = dirname(__DIR__) . '/resources/build/result/sprite-from-files-paths.svg';
        $this->builder->buildSpriteFromPaths(
            [
                dirname(__DIR__) . '/resources/build/from/icons/arrow-down.svg',
                dirname(__DIR__) . '/resources/build/from/icons/burger-menu.svg',
                dirname(__DIR__) . '/resources/build/from/icons/checkbox.svg',
                dirname(__DIR__) . '/resources/build/from/icons/icon-phone.svg',
            ],
            $spriteFromFilePath,
        );

        $this->storage->loadSprite($spriteFromFilePath);
        $content =  $this->storage->getSprite(false);

        $this->dom->preserveWhiteSpace = false;
        $this->dom->validateOnParse = true;
        $this->dom->loadXML($content);

        $svgNode = $this->dom->getElementsByTagName('svg')->item(0);

        $this->assertsForSvgSprite($svgNode);
    }
}
