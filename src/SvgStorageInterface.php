<?php
declare(strict_types=1);

namespace SvgReuser;

interface SvgStorageInterface
{
    public function add(string $id, string $data): void;

    public function getAll(string $class = null): string; //todo: maybe getSprite?

    public function get(string $id): string; //todo: maybe use?
}