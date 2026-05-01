<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 15:45
 */

namespace Oasis\Mlib\Utils\Validators;

use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;

class IntegerValidator implements ValidatorInterface
{
    public function __construct(
        private readonly bool $strict = false,
        private readonly int $base = 10,
    ) {
    }
    
    public function validate(mixed $target): mixed
    {
        if (!$this->strict
            && (is_string($target) || is_float($target))
        ) {
            $intval = intval($target, $this->base);
            if (strval($intval) == strval($target)) {
                //echo(sprintf("%s equals %s\n", print_r($intval, true), print_r($target, true)));
                $target = $intval;
            }
        }
        
        if (!is_int($target)) {
            throw new InvalidDataTypeException("Validated data is not integer!");
        }
    
        return $target;
    }
}
