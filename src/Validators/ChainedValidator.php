<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 21:18
 */

namespace Oasis\Mlib\Utils\Validators;

class ChainedValidator implements ValidatorInterface
{
    /** @var  ValidatorInterface[] */
    protected $validators;
    
    public function __construct(...$args)
    {
        foreach ($args as $arg) {
            if (!$arg instanceof ValidatorInterface) {
                throw new \InvalidArgumentException(
                    __CLASS__ . " will only accept " . ValidatorInterface::class . " constructor arguments"
                );
            }
        }
        $this->validators = $args;
    }
    
    public function validate($target)
    {
        foreach ($this->validators as $validator) {
            $target = $validator->validate($target);
        }
        
        return $target;
    }
}
