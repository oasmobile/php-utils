<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 17:28
 */

namespace Oasis\Mlib\Utils\Validators;

use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;

class BooleanValidator implements ValidatorInterface
{
    /** @var bool only boolean true/false are considered valid in strict mode */
    protected $strict = false;
    
    public function __construct($strict = false)
    {
        $this->strict = $strict;
    }
    
    public function validate($target)
    {
        if (!$this->strict) {
            if (is_string($target)) {
                $strval = strtolower($target);
                if (in_array(
                    $strval,
                    [
                        "true",
                        "on",
                        "1",
                        "yes",
                    ],
                    true
                )) {
                    $target = true;
                }
                elseif (in_array(
                    $strval,
                    [
                        "false",
                        "off",
                        "0",
                        "no",
                        "",
                    ],
                    true
                )) {
                    $target = false;
                }
            }
            elseif (is_int($target) || is_float($target)) {
                if ($target == 1) {
                    $target = true;
                }
                elseif ($target == 0) {
                    $target = false;
                }
            }
        }
        
        if (!is_bool($target)) {
            throw new InvalidDataTypeException("Validated data is not boolean!");
        }
        
        return $target;
    }
}
