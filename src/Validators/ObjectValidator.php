<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 17:29
 */

namespace Oasis\Mlib\Utils\Validators;

use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;

class ObjectValidator implements ValidatorInterface
{
    public function __construct(
        private readonly bool $allowNull = true,
    ) {
    }
    
    public function validate(mixed $target): mixed
    {
        if (is_null($target) && $this->allowNull) {
            return null;
        }
        
        if (!is_object($target)) {
            throw new InvalidDataTypeException("Validated data is not an object!");
        }
        
        return $target;
    }
}
