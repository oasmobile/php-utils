<?php
declare(strict_types=1);
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Validators\Array2DValidator;
use Oasis\Mlib\Utils\Validators\ArrayValidator;
use Oasis\Mlib\Utils\Validators\IntegerValidator;
use Oasis\Mlib\Utils\Validators\ValidatorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 18:36
 */
class ArrayValidatorTest extends TestCase
{
    /**
     * @param $target
     */
    #[DataProvider('getInvalidInputInAllowNull')]
    public function testAllowNullInvalidInput($target)
    {
        $validator = new ArrayValidator(true);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_array($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getValidInputInAllowNull')]
    public function testAllowNullValid($target)
    {
        $validator = new ArrayValidator(true);
        
        $this->assertTrue(is_array($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getInvalidInputInNotAllowNull')]
    public function testNotAllowNullInvalidInput($target)
    {
        $validator = new ArrayValidator(false);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_array($validator->validate($target)));
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getValidInputInNotAllowNull')]
    public function testNotAllowNullValid($target)
    {
        $validator = new ArrayValidator(false);
        
        $this->assertTrue(is_array($validator->validate($target)));
    }
    
    /**
     * @param                    $target
     * @param ValidatorInterface $validator
     */
    #[DataProvider('getValidInputForSpecificValidator')]
    public function testSpecificValidatorWithValidInput($target, $validator)
    {
        $this->assertTrue(is_array($validator->validate($target)));
    }
    
    public static function getValidInputForSpecificValidator()
    {
        return [
            [[1, 2, 3], new ArrayValidator(false, new IntegerValidator())],
            [['12', '45'], new ArrayValidator(false, new IntegerValidator())],
            [[[], []], new Array2DValidator()],
        ];
    }
    
    /**
     * @param                    $target
     * @param ValidatorInterface $validator
     */
    #[DataProvider('getInvalidInputForSpecificValidator')]
    public function testSpecificValidatorWithInvalidInput($target, $validator)
    {
        $this->expectException(InvalidDataTypeException::class);
        $this->assertTrue(is_array($validator->validate($target)));
    }
    
    public static function getInvalidInputForSpecificValidator()
    {
        return [
            [[12.0, 4.5, 0.0], new ArrayValidator(false, new IntegerValidator())],
            [[1, '4.5', 0.0], new ArrayValidator(false, new IntegerValidator())],
            [[[], 1, []], new Array2DValidator()],
        
        ];
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
            [new stdClass()],
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
            [new stdClass()],
        ];
    }
    
    public static function getValidInputInNotAllowNull()
    {
        return [
            [[]],
            [[123]],
            [['']],
            [[1.0, true, false, new stdClass()]],
        ];
    }
    
    public static function getValidInputInAllowNull()
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
