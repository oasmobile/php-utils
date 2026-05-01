<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 17:29
 */

namespace Oasis\Mlib\Utils\Validators;

use Oasis\Mlib\Utils\Exceptions\InvalidArrayElementException;
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;

class ArrayValidator implements ValidatorInterface
{
    protected ValidatorInterface $elementValidator;
    
    public function __construct(
        private readonly bool $allowNull = false,
        ?ValidatorInterface $elementValidator = null,
    ) {
        $this->elementValidator = $elementValidator ?? new DummyValidator();
    }
    
    public function validate(mixed $target): mixed
    {
        if (is_null($target) && $this->allowNull) {
            return [];
        }
        if (!is_array($target)) {
            throw new InvalidDataTypeException("Target is not an array!");
        }
        
        $result = [];
        foreach ($target as $k => $item) {
            try {
                $result[$k] = $this->elementValidator->validate($item);
            } catch (InvalidDataTypeException $e) {
                throw new InvalidArrayElementException(
                    sprintf(
                        'Invalid element in array for index %s, reason = %s',
                        $k,
                        $e->getMessage()
                    )
                );
            }
        }
        
        return $result;
    }
}
