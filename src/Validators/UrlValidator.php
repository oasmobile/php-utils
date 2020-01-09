<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 22:20
 */

namespace Oasis\Mlib\Utils\Validators;

use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;

/**
 * Class UrlValidator
 *
 * @package Oasis\Mlib\Utils\Validators
 */
class UrlValidator implements ValidatorInterface
{
    public function validate($target)
    {
        if (!is_string($target)) {
            throw new InvalidDataTypeException("Target is not a string, and cannot be validated as URL!");
        }
        
        $filtered = filter_var(
            $target,
            FILTER_VALIDATE_URL
        );
        if ($filtered === false) {
            throw new InvalidDataTypeException("Target is not a valid url: " . $target);
        }
        
        return $filtered;
    }
}
    
