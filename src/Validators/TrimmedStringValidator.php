<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 15:16
 */

namespace Oasis\Mlib\Utils\Validators;

use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\TrimDirection;

class TrimmedStringValidator implements ValidatorInterface
{
    public function __construct(
        private readonly bool $strict = false,
        private readonly TrimDirection $direction = TrimDirection::Both,
        private readonly string $characters = " \n\t\r\0\x0B",
    ) {
    }
    
    public function validate(mixed $target): mixed
    {
        if (!$this->strict) {
            if (is_bool($target)) {
                $target = $target ? "true" : "false";
            }
            elseif (is_scalar($target)) {
                $target = strval($target);
            }
            elseif (is_object($target) && method_exists($target, '__toString')) {
                $target = strval($target);
            }
        }
        
        if (!is_string($target)) {
            throw new InvalidDataTypeException("Validated value is not a string!");
        }
        
        return match ($this->direction) {
            TrimDirection::Left  => \ltrim($target, $this->characters),
            TrimDirection::Right => \rtrim($target, $this->characters),
            TrimDirection::Both  => \trim($target, $this->characters),
        };
    }
}
