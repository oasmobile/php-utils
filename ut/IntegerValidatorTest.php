<?php
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Validators\IntegerValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 15:59
 */
class IntegerValidatorTest extends TestCase
{
    /**
     * @param $target
     */
    #[DataProvider('getInvalidInputInStrictMode')]
    public function testStrictModeInvalidInput($target)
    {
        $validator = new IntegerValidator(true);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_int($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getValidInputInStrictMode')]
    public function testStrictModeValid($target)
    {
        $validator = new IntegerValidator(true);
        
        $this->assertTrue(is_int($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getInvalidInputInNonStrictMode')]
    public function testNonStrictModeInvalidInput($target)
    {
        $validator = new IntegerValidator(false);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_int($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getValidInputInNonStrictMode')]
    public function testNonStrictModeValid($target)
    {
        $validator = new IntegerValidator(false);
        
        $this->assertTrue(is_int($validator->validate($target)));
    }
    
    public static function getInvalidInputInStrictMode()
    {
        return [
            [''],
            ['abc'],
            ['123'],
            ['123.5'],
            [10.2],
            [0.1],
            [0.0],
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
            ['123.5'],
            [10.2],
            [0.1],
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
            [PHP_INT_MAX],
            ['10'],
            ['0'],
        ];
    }
    
    public static function getValidInputInStrictMode()
    {
        return [
            [1],
            [0],
            [PHP_INT_MAX],
        ];
    }
}
