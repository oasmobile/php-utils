<?php

namespace Oasis\Mlib\Utils;

interface HierarchicalDataProviderInterface extends DataProviderInterface
{
    public function getCurrentPath(): string;

    public function setCurrentPath(string $path): void;

    public function pushPath(string $relativePath): void;

    public function popPath(): void;

    public function getPathDelimiter(): string;

    public function setPathDelimiter(string $delimiter): void;
}
