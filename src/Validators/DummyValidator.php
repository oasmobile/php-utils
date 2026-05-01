<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 17:39
 */

namespace Oasis\Mlib\Utils\Validators;

class DummyValidator implements ValidatorInterface
{
    
    public function validate(mixed $target): mixed
    {
        return $target;
    }
}
