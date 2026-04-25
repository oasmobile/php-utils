<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 17:01
 */

namespace Oasis\Mlib\Utils\Validators;

use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;

class FloatValidator implements ValidatorInterface
{
    public function __construct(
        private readonly bool $strict = false,
    ) {
    }
    
    public function validate(mixed $target): mixed
    {
        if (!$this->strict
            && (is_string($target) || is_int($target))
        ) {
            $floatval = floatval($target);
            if (strval($floatval) == strval($target)) {
                //echo(sprintf("%s equals %s\n", print_r($floatval, true), print_r($target, true)));
                $target = $floatval;
            }
        }
        
        if (!is_float($target)) {
            throw new InvalidDataTypeException("Validated data is not float!");
        }
        
        return $target;
    }
}
