<?php
declare(strict_types=1);

namespace Oasis\Mlib\Utils;

class ArrayDataProvider extends AbstractDataProvider
    implements HierarchicalDataProviderInterface
{
    protected string $delimeter = ".";
    protected array $paths      = [];

    function __construct(private readonly array $data)
    {
    }

    protected function getValue(string $key): mixed
    {
        return $this->getRealValue($key, true);
    }

    protected function getRealValue(string $key, bool $isRelative = false): mixed
    {
        $data = $this->data;
        if ($isRelative && $this->paths) {
            $data = $this->getRealValue(implode($this->delimeter, $this->paths));
            if (!is_array($data)) {
                return null;
            }
        }

        $parts     = explode($this->delimeter, $key);
        $branchKey = '';
        while (sizeof($parts) > 0) {
            $currentKey = implode($this->delimeter, $parts);
            if ($branchKey == '' && array_key_exists($currentKey, $data)) {
                return $data[$currentKey];
            }
            $branchKey .= (strlen($branchKey) > 0 ? '.' : '') . $parts[0];
            array_shift($parts);
            if (array_key_exists($branchKey, $data) && is_array($data[$branchKey])) {
                $data      = &$data[$branchKey];
                $branchKey = '';
            }
        }

        return null;
    }

    public function getPathDelimiter(): string
    {
        return $this->delimeter;
    }

    public function setPathDelimiter(string $delimeter): void
    {
        if (strlen($delimeter) != 1) {
            throw new \InvalidArgumentException(
                "Cascade delimiter should be a single character. given = " . $delimeter
            );
        }
        $this->delimeter = $delimeter;
    }

    public function getCurrentPath(): string
    {
        return implode($this->delimeter, $this->paths);
    }

    public function setCurrentPath(string $path): void
    {
        if (!$path) {
            $this->paths = [];
        }
        else {
            $this->paths = explode($this->delimeter, $path);
        }
    }

    public function pushPath(string $relativePath): void
    {
        $parts       = explode($this->delimeter, $relativePath);
        $this->paths = array_merge($this->paths, $parts);
    }

    public function popPath(): void
    {
        if (sizeof($this->paths) > 0) {
            array_pop($this->paths);
        }
    }
}
