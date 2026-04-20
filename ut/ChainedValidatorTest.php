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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChainedValidatorTest extends TestCase
{
    /**
     * @param $val
     */
    #[DataProvider('provideChainedTestData')]
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
     * @param $val
     */
    #[DataProvider('provideInvalidChainedTestData')]
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
    
    public static function provideChainedTestData()
    {
        return [
            ['123'],
            [str_repeat('1', 20)],
        ];
    }
    
    public static function provideInvalidChainedTestData()
    {
        return [
            [''],
            ['ab22'],
            [str_repeat('1', 22)],
        ];
    }
}
