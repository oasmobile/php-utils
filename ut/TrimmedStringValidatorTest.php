<?php

use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Exceptions\StringTooLongException;
use Oasis\Mlib\Utils\Exceptions\StringTooShortException;
use Oasis\Mlib\Utils\Validators\StringLengthValidator;
use Oasis\Mlib\Utils\Validators\TrimmedStringValidator;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2018-05-03
 * Time: 21:59
 */
class TrimmedStringValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getValidStrings
     *
     * @param $target
     * @param $expectation
     * @param $direction
     * @param $chars
     */
    public function testValidStrings($target, $expectation, $direction, $chars)
    {
        $validator = new TrimmedStringValidator(true, $direction, $chars);
        $this->assertEquals($expectation, $validator->validate($target));
    }
    
    /**
     * @dataProvider getInvalidStrings
     *
     * @param $target
     */
    public function testInvalidStrings($target)
    {
        $validator = new TrimmedStringValidator(true);
        try {
            $validator->validate($target);
        } catch (Exception $e) {
            $this->assertTrue($e instanceof InvalidDataTypeException);
        }
    }
    
    public function getValidStrings()
    {
        return [
            ['   abcde  ', 'abcde', TrimmedStringValidator::TRIM_BOTH, " \n\r\t\0\0x0B"],
            ['abcde  ', 'abcde', TrimmedStringValidator::TRIM_BOTH, " \n\r\t\0\0x0B"],
            ['   abcde', 'abcde', TrimmedStringValidator::TRIM_BOTH, " \n\r\t\0\0x0B"],
            ['   abcde  ', 'abcde  ', TrimmedStringValidator::TRIM_LEFT, " \n\r\t\0\0x0B"],
            ['abcde  ', 'abcde  ', TrimmedStringValidator::TRIM_LEFT, " \n\r\t\0\0x0B"],
            ['   abcde', 'abcde', TrimmedStringValidator::TRIM_LEFT, " \n\r\t\0\0x0B"],
            ['   abcde  ', '   abcde', TrimmedStringValidator::TRIM_RIGHT, " \n\r\t\0\0x0B"],
            ['abcde  ', 'abcde', TrimmedStringValidator::TRIM_RIGHT, " \n\r\t\0\0x0B"],
            ['   abcde', '   abcde', TrimmedStringValidator::TRIM_RIGHT, " \n\r\t\0\0x0B"],
            ['abc', 'b', TrimmedStringValidator::TRIM_BOTH, "ac"],
        ];
    }
    
    public function getInvalidStrings()
    {
        return [
            [0],
            [CURLOPT_SSL_FALSESTART],
            [null],
        ];
    }
}
