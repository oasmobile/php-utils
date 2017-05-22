<?php
use Oasis\Mlib\Utils\Exceptions\DataValidationException;
use Oasis\Mlib\Utils\Validators\StringValidator;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 16:35
 */
class StringValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getValidDataForNonStrictMode
     *
     * @param $target
     */
    public function testNonStrictInputWithValidInput($target)
    {
        $validator = new StringValidator();
        $this->assertTrue(is_string($validator->validate($target)));
    }
    
    /**
     * @dataProvider getInvalidDataForNonStrictMode
     *
     * @param $target
     */
    public function testNonStrictInputWithInvalidInput($target)
    {
        $validator = new StringValidator();
        $this->expectException(DataValidationException::class);
        $this->assertTrue(is_string($validator->validate($target)));
    }
    
    /**
     * @dataProvider getValidDataForStrictMode
     *
     * @param $target
     */
    public function testStrictInputWithValidInput($target)
    {
        $validator = new StringValidator(true);
        $this->assertTrue(is_string($validator->validate($target)));
    }
    
    /**
     * @dataProvider getInvalidDataForStrictMode
     *
     * @param $target
     */
    public function testStrictInputWithInvalidInput($target)
    {
        $validator = new StringValidator(true);
        $this->expectException(DataValidationException::class);
        $this->assertTrue(is_string($validator->validate($target)));
    }
    
    /**
     * @dataProvider getValidDataForStrictModeWithEmptyNotAllowed
     *
     * @param $target
     */
    public function testStrictInputWithEmptyNotAllowed($target)
    {
        $validator = new StringValidator(true, false);
        $this->assertTrue(is_string($validator->validate($target)));
    }
    
    /**
     * @dataProvider getInvalidDataForStrictModeWithEmptyNotAllowed
     *
     * @param $target
     */
    public function testStrictInputWithInvalidInputEmptyNotAllowed($target)
    {
        $validator = new StringValidator(true, false);
        $this->expectException(DataValidationException::class);
        $this->assertTrue(is_string($validator->validate($target)));
        
    }
    
    public function getValidDataForNonStrictMode()
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
    
    public function getInvalidDataForNonStrictMode()
    {
        return [
            [[]],
            [['abc']],
            [new stdClass()],
            [null],
        ];
    }
    
    public function getValidDataForStrictMode()
    {
        return [
            ["abc"],
            [""],
        ];
    }
    
    public function getValidDataForStrictModeWithEmptyNotAllowed()
    {
        return [
            ["abc"],
        ];
    }
    
    public function getInvalidDataForStrictMode()
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
    
    public function getInvalidDataForStrictModeWithEmptyNotAllowed()
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
