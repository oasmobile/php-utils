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
    /** @var bool in non-strict mode, a string which can be parsed to float, or an integer, is also considered valid */
    protected $strict = false;
    
    public function __construct($strict = false)
    {
        $this->strict = $strict;
    }
    
    public function validate($target)
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
