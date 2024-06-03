<?php

declare(strict_types=1);

namespace SvgReuser;

readonly class Svg
{
    public function __construct(
        private string  $id,
        private string  $data,
        private ?string $class = null
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function getAnchor(): string
    {
        $classHtml = $this->getClass() !== null ? sprintf('class="%s"', $this->getClass()) : '';

        return sprintf('<svg %s><use href="#%s" x="0" y="0" /></svg>', $classHtml, $this->getId());
    }

    public function getSymbol(): string
    {
        $content = $this->data;

        return str_replace(['<svg', '</svg>'],
            [sprintf('<symbol id="%s"', $this->getId()), '</symbol>'],
            $content);
    }
}