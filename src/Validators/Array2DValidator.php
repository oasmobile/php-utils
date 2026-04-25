<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 17:31
 */

namespace Oasis\Mlib\Utils\Validators;

class Array2DValidator extends ArrayValidator
{
    public function __construct(bool $allowNull = true, ?ValidatorInterface $elementValidator = null)
    {
        parent::__construct($allowNull, new ArrayValidator($allowNull, $elementValidator));
    }
    
}
