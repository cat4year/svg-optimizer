<?php
declare(strict_types=1);

namespace SvgReuser;

interface SvgStorageInterface
{
    public function add(string $id, string $data): void;

    public function getSprite(string $class = null): string;

    public function get(string $id): string;
}