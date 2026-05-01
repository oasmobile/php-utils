<?php
declare(strict_types=1);

use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Exceptions\StringTooLongException;
use Oasis\Mlib\Utils\Exceptions\StringTooShortException;
use Oasis\Mlib\Utils\TrimDirection;
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
            ['   abcde  ', 'abcde', TrimDirection::Both, " \n\r\t\0\0x0B"],
            ['abcde  ', 'abcde', TrimDirection::Both, " \n\r\t\0\0x0B"],
            ['   abcde', 'abcde', TrimDirection::Both, " \n\r\t\0\0x0B"],
            ['   abcde  ', 'abcde  ', TrimDirection::Left, " \n\r\t\0\0x0B"],
            ['abcde  ', 'abcde  ', TrimDirection::Left, " \n\r\t\0\0x0B"],
            ['   abcde', 'abcde', TrimDirection::Left, " \n\r\t\0\0x0B"],
            ['   abcde  ', '   abcde', TrimDirection::Right, " \n\r\t\0\0x0B"],
            ['abcde  ', 'abcde', TrimDirection::Right, " \n\r\t\0\0x0B"],
            ['   abcde', '   abcde', TrimDirection::Right, " \n\r\t\0\0x0B"],
            ['abc', 'b', TrimDirection::Both, "ac"],
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

    public function testNonStrictModeConvertsBool(): void
    {
        $validator = new TrimmedStringValidator(false);
        $this->assertEquals('true', $validator->validate(true));
        $this->assertEquals('false', $validator->validate(false));
    }

    public function testNonStrictModeConvertsScalar(): void
    {
        $validator = new TrimmedStringValidator(false);
        $this->assertEquals('42', $validator->validate(42));
        $this->assertEquals('3.14', $validator->validate(3.14));
    }

    public function testNonStrictModeConvertsToString(): void
    {
        $obj = new class {
            public function __toString(): string
            {
                return '  hello  ';
            }
        };
        $validator = new TrimmedStringValidator(false);
        $this->assertEquals('hello', $validator->validate($obj));
    }

    public function testNonStrictModeRejectsNonConvertible(): void
    {
        $validator = new TrimmedStringValidator(false);
        $this->expectException(\Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException::class);
        $validator->validate([]);
    }
}
