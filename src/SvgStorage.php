<?php

declare(strict_types=1);

namespace SvgReuser;

class SvgStorage implements SvgStorageInterface
{
    /**
     * @var Svg[]
     */
    private array $storage = [];

    public function add(string $id, string $data): void
    {
        $class = null;

        $pattern = '/class="(.*?)"/i';
        preg_match_all($pattern, $data, $matches);
        if (isset($matches[0][0], $matches[1][0])) {
            $classWithAttribute = ' ' . $matches[0][0];
            $class = $matches[1][0];

            $data = str_replace($classWithAttribute, '', $data);
        }

        $svg = new Svg($id, $data, $class);

        $this->storage[$id] = $svg;
    }

    public function getAll(string $class = null): string
    {
        $classHtml = $class !== null ? sprintf('class="%s"', $class) : '';
        $result = sprintf('<svg %s xmlns="http://www.w3.org/2000/svg">', $classHtml);

        foreach ($this->storage as $svg) {
            $result .= $svg->getSymbol();
        }

        $result .= '</svg>';

        return $result;
    }

    public function get(string $id): string
    {
        if (!array_key_exists($id, $this->storage)) {
            return '';
        }

        $svg = $this->storage[$id];

        return $svg->getAnchor();
    }
}