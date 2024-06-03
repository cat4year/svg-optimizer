<?php

declare(strict_types=1);

namespace SvgReuser\tests\unit;

use enshrined\svgSanitize\Sanitizer;
use PHPUnit\Framework\TestCase;
use SvgReuser\SvgStorage;

class SvgStorageTest extends TestCase
{
    protected function getSvgContent($filename): string
    {
        $filePath = __DIR__ . "/../resources/images/{$filename}";

        return file_get_contents($filePath);
    }

    private function sanitizeSvg(string $svg): false|string
    {
        return (new Sanitizer())->sanitize($svg);
    }

    private function bootstrap(): SvgStorage
    {
        $svgStorage = new SvgStorage();

        $svgFirstContent = $this->getSvgContent('svg-first.svg');
        $svgSecondContent = $this->getSvgContent('svg-second.svg');

        $svgStorage->add('svg-first', $svgFirstContent);
        $svgStorage->add('svg-second', $svgSecondContent);

        return $svgStorage;
    }

    public function testSvgSprite(): void
    {
        $svgStorage = $this->bootstrap();

        $svgSprite = $svgStorage->getAll('d-none');

        $this->assertIsString($this->sanitizeSvg($svgSprite));
    }

    public function testSvgUse(): void
    {
        $svgStorage = $this->bootstrap();

        $someSvgUseCode = $svgStorage->get('svg-first');

        $this->assertIsString($this->sanitizeSvg($someSvgUseCode));
    }
}
