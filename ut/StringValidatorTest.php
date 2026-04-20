<?php
use Oasis\Mlib\Utils\Exceptions\DataValidationException;
use Oasis\Mlib\Utils\Validators\StringValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 16:35
 */
class StringValidatorTest extends TestCase
{
    /**
     * @param $target
     */
    #[DataProvider('getValidDataForNonStrictMode')]
    public function testNonStrictInputWithValidInput($target)
    {
        $validator = new StringValidator();
        $this->assertTrue(is_string($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getInvalidDataForNonStrictMode')]
    public function testNonStrictInputWithInvalidInput($target)
    {
        $validator = new StringValidator();
        $this->expectException(DataValidationException::class);
        $this->assertTrue(is_string($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getValidDataForStrictMode')]
    public function testStrictInputWithValidInput($target)
    {
        $validator = new StringValidator(true);
        $this->assertTrue(is_string($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getInvalidDataForStrictMode')]
    public function testStrictInputWithInvalidInput($target)
    {
        $validator = new StringValidator(true);
        $this->expectException(DataValidationException::class);
        $this->assertTrue(is_string($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getValidDataForStrictModeWithEmptyNotAllowed')]
    public function testStrictInputWithEmptyNotAllowed($target)
    {
        $validator = new StringValidator(true, false);
        $this->assertTrue(is_string($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getInvalidDataForStrictModeWithEmptyNotAllowed')]
    public function testStrictInputWithInvalidInputEmptyNotAllowed($target)
    {
        $validator = new StringValidator(true, false);
        $this->expectException(DataValidationException::class);
        $this->assertTrue(is_string($validator->validate($target)));
        
    }
    
    public static function getValidDataForNonStrictMode()
    {
        return [
            ["abc"],
            [""],
            [1],
            [0],
            [1.1],
            [true],
            [false],
        
        ];
    }
    
    public static function getInvalidDataForNonStrictMode()
    {
        return [
            [[]],
            [['abc']],
            [new stdClass()],
            [null],
        ];
    }
    
    public static function getValidDataForStrictMode()
    {
        return [
            ["abc"],
            [""],
        ];
    }
    
    public static function getValidDataForStrictModeWithEmptyNotAllowed()
    {
        return [
            ["abc"],
        ];
    }
    
    public static function getInvalidDataForStrictMode()
    {
        return [
            [1],
            [0],
            [1.1],
            [true],
            [false],
            [null],
            [[]],
            [['abc']],
            [new stdClass()],
        ];
    }
    
    public static function getInvalidDataForStrictModeWithEmptyNotAllowed()
    {
        return [
            [""],
            [1],
            [0],
            [1.1],
            [true],
            [false],
            [null],
            [[]],
            [['abc']],
            [new stdClass()],
        ];
    }
}
