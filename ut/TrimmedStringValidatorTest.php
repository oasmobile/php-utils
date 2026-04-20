<?php

use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Exceptions\StringTooLongException;
use Oasis\Mlib\Utils\Exceptions\StringTooShortException;
use Oasis\Mlib\Utils\Validators\StringLengthValidator;
use Oasis\Mlib\Utils\Validators\TrimmedStringValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2018-05-03
 * Time: 21:59
 */
class TrimmedStringValidatorTest extends TestCase
{
    /**
     * @param $target
     * @param $expectation
     * @param $direction
     * @param $chars
     */
    #[DataProvider('getValidStrings')]
    public function testValidStrings($target, $expectation, $direction, $chars)
    {
        $validator = new TrimmedStringValidator(true, $direction, $chars);
        $this->assertEquals($expectation, $validator->validate($target));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getInvalidStrings')]
    public function testInvalidStrings($target)
    {
        $validator = new TrimmedStringValidator(true);
        try {
            $validator->validate($target);
        } catch (Exception $e) {
            $this->assertTrue($e instanceof InvalidDataTypeException);
        }
    }
    
    public static function getValidStrings()
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
    
    public static function getInvalidStrings()
    {
        return [
            [0],
            [CURLOPT_SSL_FALSESTART],
            [null],
        ];
    }
}
