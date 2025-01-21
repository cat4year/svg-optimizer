<?php

declare(strict_types=1);

namespace Cat4year\SvgReuser\Sanitizer;

interface Sanitizer
{
    public function sanitize(string $xml): string;
}
