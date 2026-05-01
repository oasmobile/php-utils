<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 21:38
 */

namespace Oasis\Mlib\Utils\Validators;

use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;

/**
 * Class EmailValidator
 *
 * @package Oasis\Mlib\Utils\Validators
 *
 * This class adds checks based on filter_var(), according to https://www.jochentopf.com/email/chars.html
 */
class EmailValidator implements ValidatorInterface
{
    public function validate(mixed $target): mixed
    {
        if (!is_string($target)) {
            throw new InvalidDataTypeException("Target is not a string, and cannot be validated as Email!");
        }
        
        if (preg_match('/[#!$%|&]/', $target)) {
            throw new InvalidDataTypeException("Target contains special character which cannot be part of an Email!");
        }
        
        $filtered = filter_var($target, FILTER_VALIDATE_EMAIL);
        if ($filtered === false) {
            throw new InvalidDataTypeException("Target is not a valid email: " . $target);
        }
        
        return $filtered;
    }
}
