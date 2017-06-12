<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2017-06-12
 * Time: 11:26
 */

namespace Oasis\Mlib\Utils\Validators;

use Oasis\Mlib\Utils\Exceptions\InvalidValueException;

class EnumerationValidator implements ValidatorInterface
{
    /**
     * @var array
     */
    private $values;
    /**
     * @var bool
     */
    private $strictType;
    /**
     * @var bool
     */
    private $caseSensitive;
    
    public function __construct(array $values, $strictType = false, $caseSensitive = true)
    {
        if ($caseSensitive) {
            $this->values = $values;
        }
        else {
            $this->values = \array_map(
                function ($v) {
                    return \is_string($v) ? \strtolower($v) : $v;
                },
                $values
            );
        }
        $this->strictType    = $strictType;
        $this->caseSensitive = $caseSensitive;
        
    }
    
    public function validate($target)
    {
        $origTarget = $target;
        if (!$this->caseSensitive && \is_string($target)) {
            $target = \strtolower($target);
        }
        if (!\in_array($target, $this->values, $this->strictType)) {
            throw new InvalidValueException(
                \sprintf("Value %s is not in the enumeration list!", \print_r($target, true))
            );
        }
        
        return $origTarget;
    }
}
