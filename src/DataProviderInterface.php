<?php

namespace Oasis\Mlib\Utils;

use Oasis\Mlib\Utils\Validators\ValidatorInterface;

interface DataProviderInterface
{
    public function has(string $key, ValidatorInterface|DataType $validator = DataType::Mixed): bool;

    public function get(string $key, ValidatorInterface|DataType $validator = DataType::String, bool $isMandatory = false, mixed $default = null): mixed;

    public function getMandatory(string $key, ValidatorInterface|DataType $validator = DataType::String): mixed;

    public function getOptional(string $key, ValidatorInterface|DataType $validator = DataType::String, mixed $default = null): mixed;
}
