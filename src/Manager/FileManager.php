<?php

declare(strict_types=1);

namespace Cat4year\SvgReuser\Manager;

use Cat4year\SvgReuser\SvgException;
use ErrorException;

final class FileManager
{
    /**
     * @throws SvgException
     * @throws ErrorException
     */
    public function getFromFile(string $filePath): string
    {
        $content = file_get_contents($filePath);

        if (false === $content) {
            throw new SvgException(sprintf('File "%s" was not found', $filePath));
        }

        return $content;
    }

    /**
     * @throws SvgException
     */
    public function saveToFile(string $filePath, string $content): void
    {
        $dirPath = dirname($filePath);

        if (!is_dir($dirPath) && !mkdir($dirPath, 0777, true) && !is_dir($dirPath)) {
            throw new SvgException(sprintf('Directory "%s" was not created', $dirPath));
        }

        if (file_put_contents($filePath, $content) === false) {
            throw new SvgException(sprintf('File "%s" was not created', $filePath));
        }
    }

    public function collectSvgFiles(string $directory): array
    {
        return $this->rglob(rtrim($directory, '/') . '/**.svg');
    }

    private function rglob($pattern, $flags = 0): false|array
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge([], ...[$files, $this->rglob($dir . "/" . basename($pattern), $flags)]);
        }

        return $files;
    }
}
