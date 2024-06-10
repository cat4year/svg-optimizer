<?php

declare(strict_types=1);

namespace SvgReuser;

use DOMDocument;

class DomParser
{

    public function __construct(private DOMDocument $document)
    {
    }
}