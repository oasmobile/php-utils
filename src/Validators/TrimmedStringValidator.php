<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 15:16
 */

namespace Oasis\Mlib\Utils\Validators;

use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;

class TrimmedStringValidator implements ValidatorInterface
{
    const TRIM_BOTH  = 'both';
    const TRIM_LEFT  = 'left';
    const TRIM_RIGHT = 'right';
    
    /** @var bool if strict, only string is allowed */
    protected $strict = false;
    /**
     * @var string
     */
    private $direction;
    /**
     * @var string
     */
    private $characters;
    
    public function __construct($strict = false, $direction = self::TRIM_BOTH, $characters = " \n\t\r\0\x0B")
    {
        $this->strict     = $strict;
        $this->direction  = $direction;
        $this->characters = $characters;
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
            elseif (is_object($target) && method_exists($target, '__toString()')) {
                $target = strval($target);
            }
        }
        
        if (!is_string($target)) {
            throw new InvalidDataTypeException("Validated value is not a string!");
        }
        
        switch ($this->direction) {
            case self::TRIM_LEFT:
                return \ltrim($target, $this->characters);
            case self::TRIM_RIGHT:
                return \rtrim($target, $this->characters);
            case self::TRIM_BOTH:
            default:
                return \trim($target, $this->characters);
        }
    }
}
