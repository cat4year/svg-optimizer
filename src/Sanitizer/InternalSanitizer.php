<?php

declare(strict_types=1);

namespace Cat4year\SvgReuser\Sanitizer;

use enshrined\svgSanitize\Sanitizer as EnshrinedSanitizer;

final readonly class InternalSanitizer implements Sanitizer
{
    private EnshrinedSanitizer $sanitizer;

    public function __construct(bool $needMinify = true)
    {
        $this->sanitizer = new EnshrinedSanitizer();
        $this->sanitizer->minify($needMinify);
    }

    public function sanitize(string $xml): string
    {
        return $this->sanitizer->sanitize($xml);
    }
}