<?php

declare(strict_types=1);

namespace SvgReuser\Tests\Unit;

use Exception;
use SvgReuser\DefinitionIdentificationEnum;
use SvgReuser\SvgSpriteBuilder;

final class SvgSpriteBuilderTest extends AbstractSvg
{
    private SvgSpriteBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new SvgSpriteBuilder(definitionIdOrderedList: [
            DefinitionIdentificationEnum::ID,
            DefinitionIdentificationEnum::SVG_CLASS,
            DefinitionIdentificationEnum::ORDINAL,
            DefinitionIdentificationEnum::HASH,
        ]);
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

        ob_start();
        $this->storage->showSprite(false);
        $content = ob_get_clean();

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

        ob_start();
        $this->storage->showSprite(false);
        $content = ob_get_clean();

        $this->dom->preserveWhiteSpace = false;
        $this->dom->validateOnParse = true;
        $this->dom->loadXML($content);

        $svgNode = $this->dom->getElementsByTagName('svg')->item(0);
        $this->assertsForSvgSprite($svgNode);
    }
}
