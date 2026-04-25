<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 21:20
 */

namespace Oasis\Mlib\Utils\Validators;

use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Exceptions\RegexNotMatchedException;

class RegexValidator implements ValidatorInterface
{
    public function __construct(
        private readonly string $pattern,
    ) {
        if (@preg_match($pattern, '') === false) {
            throw new \InvalidArgumentException("Invalid pattern: " . $pattern);
        }
    }
    
    public function validate(mixed $target): mixed
    {
        if (!is_string($target)) {
            throw new InvalidDataTypeException("Target is not a string, and cannot be validated by REGEX!");
        }
    
        if (!preg_match($this->pattern, $target)) {
            throw new RegexNotMatchedException("Target given cannot be matched by REGEX!");
        }
    
        return $target;
    }
}
