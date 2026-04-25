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
    private array $values;
    
    public function __construct(
        array $values,
        private readonly bool $strictType = false,
        private readonly bool $caseSensitive = true,
    ) {
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
    }
    
    public function validate(mixed $target): mixed
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
