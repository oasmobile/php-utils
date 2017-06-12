<?php
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Validators\ObjectValidator;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 18:36
 */
class ObjectValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getInvalidInputInAllowNull
     *
     * @param $target
     */
    public function testAllowNullInvalidInput($target)
    {
        $validator = new ObjectValidator(true);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_null($target) || is_object($validator->validate($target)));
    }
    
    /**
     * @dataProvider getValidInputInAllowNull
     *
     * @param $target
     */
    public function testAllowNullValid($target)
    {
        $validator = new ObjectValidator(true);
        
        $this->assertTrue(is_null($target) || is_object($validator->validate($target)));
    }
    
    /**
     * @dataProvider getInvalidInputInNotAllowNull
     *
     * @param $target
     */
    public function testNotAllowNullInvalidInput($target)
    {
        $validator = new ObjectValidator(false);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_object($validator->validate($target)));
    }
    
    /**
     * @dataProvider getValidInputInNotAllowNull
     *
     * @param $target
     */
    public function testNotAllowNullValid($target)
    {
        $validator = new ObjectValidator(false);
        
        $this->assertTrue(is_object($validator->validate($target)));
    }
    
    public function getInvalidInputInAllowNull()
    {
        return [
            [''],
            ['on'],
            ['1'],
            ['0.0'],
            [1],
            [0],
            [1.0],
            [0.0],
            [true],
            [false],
            [[]],
            [[123]],
        ];
    }
    
    public function getInvalidInputInNotAllowNull()
    {
        return [
            [''],
            ['on'],
            ['1'],
            ['0.0'],
            [1],
            [0],
            [1.0],
            [0.0],
            [null],
            [true],
            [false],
            [[]],
            [[123]],
        ];
    }
    
    public function getValidInputInNotAllowNull()
    {
        return [
            [new stdClass()],
        ];
    }
    
    public function getValidInputInAllowNull()
    {
        return [
            [new stdClass()],
            [null],
        ];
    }
    
}
