<?php
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Validators\FloatValidator;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 15:59
 */
class FloatValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getInvalidInputInStrictMode
     *
     * @param $target
     */
    public function testStrictModeInvalidInput($target)
    {
        $validator = new FloatValidator(true);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_float($validator->validate($target)));
    }
    
    /**
     * @dataProvider getValidInputInStrictMode
     *
     * @param $target
     */
    public function testStrictModeValid($target)
    {
        $validator = new FloatValidator(true);
        
        $this->assertTrue(is_float($validator->validate($target)));
    }
    
    /**
     * @dataProvider getInvalidInputInNonStrictMode
     *
     * @param $target
     */
    public function testNonStrictModeInvalidInput($target)
    {
        $validator = new FloatValidator(false);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_float($validator->validate($target)));
    }
    
    /**
     * @dataProvider getValidInputInNonStrictMode
     *
     * @param $target
     */
    public function testNonStrictModeValid($target)
    {
        $validator = new FloatValidator(false);
        
        $this->assertTrue(is_float($validator->validate($target)));
    }
    
    public function getInvalidInputInStrictMode()
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
    
    public function getInvalidInputInNonStrictMode()
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
    
    public function getValidInputInNonStrictMode()
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
    
    public function getValidInputInStrictMode()
    {
        return [
            [1.0],
            [0.0],
        ];
    }
}
