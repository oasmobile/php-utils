<?php
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Validators\Array2DValidator;
use Oasis\Mlib\Utils\Validators\ArrayValidator;
use Oasis\Mlib\Utils\Validators\IntegerValidator;
use Oasis\Mlib\Utils\Validators\ValidatorInterface;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 18:36
 */
class ArrayValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getInvalidInputInAllowNull
     *
     * @param $target
     */
    public function testAllowNullInvalidInput($target)
    {
        $validator = new ArrayValidator(true);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_array($validator->validate($target)));
    }
    
    /**
     * @dataProvider getValidInputInAllowNull
     *
     * @param $target
     */
    public function testAllowNullValid($target)
    {
        $validator = new ArrayValidator(true);
        
        $this->assertTrue(is_array($validator->validate($target)));
    }
    
    /**
     * @dataProvider getInvalidInputInNotAllowNull
     *
     * @param $target
     */
    public function testNotAllowNullInvalidInput($target)
    {
        $validator = new ArrayValidator(false);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_array($validator->validate($target)));
    }
    
    /**
     * @dataProvider getValidInputInNotAllowNull
     *
     * @param $target
     */
    public function testNotAllowNullValid($target)
    {
        $validator = new ArrayValidator(false);
        
        $this->assertTrue(is_array($validator->validate($target)));
    }
    
    /**
     * @dataProvider getValidInputForSpecificValidator
     *
     * @param                    $target
     * @param ValidatorInterface $validator
     */
    public function testSpecificValidatorWithValidInput($target, $validator)
    {
        $this->assertTrue(is_array($validator->validate($target)));
    }
    
    public function getValidInputForSpecificValidator()
    {
        return [
            [[1, 2, 3], new ArrayValidator(false, new IntegerValidator())],
            [['12', '45'], new ArrayValidator(false, new IntegerValidator())],
            [[[], []], new Array2DValidator()],
        ];
    }
    
    /**
     * @dataProvider getInvalidInputForSpecificValidator
     *
     * @param                    $target
     * @param ValidatorInterface $validator
     */
    public function testSpecificValidatorWithInvalidInput($target, $validator)
    {
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_array($validator->validate($target)));
    }
    
    public function getInvalidInputForSpecificValidator()
    {
        return [
            [[12.0, 4.5, 0.0], new ArrayValidator(false, new IntegerValidator())],
            [[1, '4.5', 0.0], new ArrayValidator(false, new IntegerValidator())],
            [[[], 1, []], new Array2DValidator()],
        
        ];
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
            [new stdClass()],
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
            [new stdClass()],
        ];
    }
    
    public function getValidInputInNotAllowNull()
    {
        return [
            [[]],
            [[123]],
            [['']],
            [[1.0, true, false, new stdClass()]],
        ];
    }
    
    public function getValidInputInAllowNull()
    {
        return [
            [null],
            [[]],
            [[123]],
            [['']],
            [[1.0, true, false, new stdClass()]],
        ];
    }
    
}
