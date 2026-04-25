<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 14:43
 */

namespace Oasis\Mlib\Utils\Validators;

interface ValidatorInterface
{
    public function validate(mixed $target): mixed;
}
