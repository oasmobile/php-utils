<?php
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Validators\ObjectValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 18:36
 */
class ObjectValidatorTest extends TestCase
{
    /**
     * @param $target
     */
    #[DataProvider('getInvalidInputInAllowNull')]
    public function testAllowNullInvalidInput($target)
    {
        $validator = new ObjectValidator(true);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_null($target) || is_object($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getValidInputInAllowNull')]
    public function testAllowNullValid($target)
    {
        $validator = new ObjectValidator(true);
        
        $this->assertTrue(is_null($target) || is_object($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getInvalidInputInNotAllowNull')]
    public function testNotAllowNullInvalidInput($target)
    {
        $validator = new ObjectValidator(false);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_object($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getValidInputInNotAllowNull')]
    public function testNotAllowNullValid($target)
    {
        $validator = new ObjectValidator(false);
        
        $this->assertTrue(is_object($validator->validate($target)));
    }
    
    public static function getInvalidInputInAllowNull()
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
    
    public static function getInvalidInputInNotAllowNull()
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
    
    public static function getValidInputInNotAllowNull()
    {
        return [
            [new stdClass()],
        ];
    }
    
    public static function getValidInputInAllowNull()
    {
        return [
            [new stdClass()],
            [null],
        ];
    }
    
}
