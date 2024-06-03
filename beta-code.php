<?php

interface SvgStorageInterface
{
    public function add(string $id, string $data): void;

    public function getAll(string $class = null): string;

    public function get(string $id): string;
}

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

$svgStorage = new SvgStorage();

$svgStorage->add('catalog-item__iconId',
    '<svg class="catalog-item__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M7.71657 3.21967C7.42781 3.51256 7.42781 3.98744 7.71657 4.28033L14.8046 11.4697C15.0933 11.7626 15.0933 12.2374 14.8046 12.5303L7.71657 19.7197C7.42781 20.0126 7.42781 20.4874 7.71657 20.7803C8.00534 21.0732 8.47352 21.0732 8.76228 20.7803L15.8503 13.591C16.7166 12.7123 16.7166 11.2877 15.8503 10.409L8.76228 3.21967C8.47352 2.92678 8.00534 2.92678 7.71657 3.21967Z" fill="currentColor"></path>
                                            </svg>');
$svgStorage->add('testId',
    '<svg class="testIdClass" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M7.71657 3.21967C7.42781 3.51256 7.42781 3.98744 7.71657 4.28033L14.8046 11.4697C15.0933 11.7626 15.0933 12.2374 14.8046 12.5303L7.71657 19.7197C7.42781 20.0126 7.42781 20.4874 7.71657 20.7803C8.00534 21.0732 8.47352 21.0732 8.76228 20.7803L15.8503 13.591C16.7166 12.7123 16.7166 11.2877 15.8503 10.409L8.76228 3.21967C8.47352 2.92678 8.00534 2.92678 7.71657 3.21967Z" fill="currentColor"></path>
                                            </svg>');

echo $svgStorage->getAll('d-none');

echo $svgStorage->get('testId');