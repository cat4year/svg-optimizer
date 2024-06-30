<?php

declare(strict_types=1);


class SvgPageManager
{
    //todo: класс который будет из общего спрайта получать только необходимые символы
    //todo: а также выводить якори на страницу
    public function get(string $id): string
    {
        if (!array_key_exists($id, $this->storage)) {
            return '';
        }

        $svg = $this->storage[$id];

        return $svg->getAnchor();
    }
}