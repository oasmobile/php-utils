<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2017-05-22
 * Time: 14:53
 */

use Oasis\Mlib\Utils\Exceptions\DataValidationException;
use Oasis\Mlib\Utils\Validators\ChainedValidator;
use Oasis\Mlib\Utils\Validators\RegexValidator;
use Oasis\Mlib\Utils\Validators\StringLengthValidator;
use Oasis\Mlib\Utils\Validators\StringValidator;

class ChainedValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideChainedTestData
     *
     * @param $val
     */
    public function testValidData($val)
    {
        $cv = new ChainedValidator(
            new StringValidator(),
            new StringLengthValidator(20),
            new RegexValidator('/^[0-9]+$/')
        );
        $this->assertEquals($val, $cv->validate($val));
    }
    
    /**
     * @dataProvider provideInvalidChainedTestData
     *
     * @param $val
     */
    public function testInvalidData($val)
    {
        $cv = new ChainedValidator(
            new StringValidator(),
            new StringLengthValidator(20),
            new RegexValidator('/^[0-9]+$/')
        );
        $this->expectException(DataValidationException::class);
        $cv->validate($val);
    }
    
    public function provideChainedTestData()
    {
        return [
            ['123'],
            [str_repeat('1', 20)],
        ];
    }
    
    public function provideInvalidChainedTestData()
    {
        return [
            [''],
            ['ab22'],
            [str_repeat('1', 22)],
        ];
    }
}
