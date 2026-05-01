<?php
declare(strict_types=1);
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Validators\BooleanValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 15:59
 */
class BooleanValidatorTest extends TestCase
{
    /**
     * @param $target
     */
    #[DataProvider('getInvalidInputInStrictMode')]
    public function testStrictModeInvalidInput($target)
    {
        $validator = new BooleanValidator(true);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_bool($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getValidInputInStrictMode')]
    public function testStrictModeValid($target)
    {
        $validator = new BooleanValidator(true);
        
        $this->assertTrue(is_bool($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getInvalidInputInNonStrictMode')]
    public function testNonStrictModeInvalidInput($target)
    {
        $validator = new BooleanValidator(false);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_bool($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getValidInputInNonStrictMode')]
    public function testNonStrictModeValid($target)
    {
        $validator = new BooleanValidator(false);
        
        $this->assertTrue(is_bool($validator->validate($target)));
    }
    
    public static function getInvalidInputInStrictMode()
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
            [new stdClass()],
            [[]],
            [[123]],
        ];
    }
    
    public static function getInvalidInputInNonStrictMode()
    {
        return [
            ['1.0'],
            ['0.0'],
            [new stdClass()],
            [[]],
            [[123]],
            [null],
        ];
    }
    
    public static function getValidInputInNonStrictMode()
    {
        return [
            [true],
            [false],
            ['on'],
            ['On'],
            ['ON'],
            ['off'],
            ['yes'],
            ['no'],
            ['true'],
            ['false'],
            [''],
            [1],
            [0],
            [1.0],
            [0.0],
        ];
    }
    
    public static function getValidInputInStrictMode()
    {
        return [
            [true],
            [false],
        ];
    }
}
