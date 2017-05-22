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
    protected $pattern;
    
    public function __construct($pattern)
    {
        if (!is_string($pattern)) {
            throw new \InvalidArgumentException("Pattern must be string: " . print_r($pattern, true));
        }
        if (@preg_match($pattern, '') === false) {
            throw new \InvalidArgumentException("Invalid pattern: " . $pattern);
        }
    
        $this->pattern = $pattern;
    }
    
    public function validate($target)
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
