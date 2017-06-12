<?php
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Validators\IntegerValidator;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 15:59
 */
class IntegerValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getInvalidInputInStrictMode
     *
     * @param $target
     */
    public function testStrictModeInvalidInput($target)
    {
        $validator = new IntegerValidator(true);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_int($validator->validate($target)));
    }
    
    /**
     * @dataProvider getValidInputInStrictMode
     *
     * @param $target
     */
    public function testStrictModeValid($target)
    {
        $validator = new IntegerValidator(true);
        
        $this->assertTrue(is_int($validator->validate($target)));
    }
    
    /**
     * @dataProvider getInvalidInputInNonStrictMode
     *
     * @param $target
     */
    public function testNonStrictModeInvalidInput($target)
    {
        $validator = new IntegerValidator(false);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_int($validator->validate($target)));
    }
    
    /**
     * @dataProvider getValidInputInNonStrictMode
     *
     * @param $target
     */
    public function testNonStrictModeValid($target)
    {
        $validator = new IntegerValidator(false);
        
        $this->assertTrue(is_int($validator->validate($target)));
    }
    
    public function getInvalidInputInStrictMode()
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
    
    public function getInvalidInputInNonStrictMode()
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
    
    public function getValidInputInNonStrictMode()
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
    
    public function getValidInputInStrictMode()
    {
        return [
            [1],
            [0],
            [PHP_INT_MAX],
        ];
    }
}
