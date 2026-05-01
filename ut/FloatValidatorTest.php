<?php
declare(strict_types=1);
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Validators\FloatValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 15:59
 */
class FloatValidatorTest extends TestCase
{
    /**
     * @param $target
     */
    #[DataProvider('getInvalidInputInStrictMode')]
    public function testStrictModeInvalidInput($target)
    {
        $validator = new FloatValidator(true);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_float($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getValidInputInStrictMode')]
    public function testStrictModeValid($target)
    {
        $validator = new FloatValidator(true);
        
        $this->assertTrue(is_float($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getInvalidInputInNonStrictMode')]
    public function testNonStrictModeInvalidInput($target)
    {
        $validator = new FloatValidator(false);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_float($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getValidInputInNonStrictMode')]
    public function testNonStrictModeValid($target)
    {
        $validator = new FloatValidator(false);
        
        $this->assertTrue(is_float($validator->validate($target)));
    }
    
    public static function getInvalidInputInStrictMode()
    {
        return [
            [''],
            ['abc'],
            ['123'],
            ['123.5'],
            [10],
            [0],
            [null],
            [true],
            [false],
            [new stdClass()],
            [[]],
            [[123]],
        ];
    }
    
    public static function getInvalidInputInNonStrictMode()
    {
        return [
            [''],
            ['abc'],
            [PHP_INT_MAX], // precision too high
            [123456789012345], // precision too high
            [null],
            [true],
            [false],
            [new stdClass()],
            [[]],
            [[123]],
        ];
    }
    
    public static function getValidInputInNonStrictMode()
    {
        return [
            [10],
            [0],
            [5.0],
            [0.0],
            [12345678901234], // precision ok
            ['10'],
            ['0'],
            ['10.3'],
            ['0.0'],
        ];
    }
    
    public static function getValidInputInStrictMode()
    {
        return [
            [1.0],
            [0.0],
        ];
    }
}
