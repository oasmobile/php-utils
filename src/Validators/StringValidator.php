<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 15:16
 */

namespace Oasis\Mlib\Utils\Validators;

use Oasis\Mlib\Utils\Exceptions\DataEmptyException;
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;

class StringValidator implements ValidatorInterface
{
    /** @var bool if strict, only string is allowed */
    protected $strict     = false;
    protected $allowEmpty = true;
    
    public function __construct($strict = false, $allowEmpty = true)
    {
        $this->strict     = $strict;
        $this->allowEmpty = $allowEmpty;
    }
    
    public function validate($target)
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
        
        if (!$this->allowEmpty && strlen($target) === 0) {
            throw new DataEmptyException("Validated string is empty!");
        }
        
        return $target;
    }
}
