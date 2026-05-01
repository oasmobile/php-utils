<?php
declare(strict_types=1);
use Oasis\Mlib\Utils\Exceptions\RegexNotMatchedException;
use Oasis\Mlib\Utils\Validators\RegexValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 22:16
 */
class RegexValidatorTest extends TestCase
{
    /**
     * @param $pattern
     * @param $target
     */
    #[DataProvider('getValidStrings')]
    public function testValidStrings($pattern, $target)
    {
        $validator = new RegexValidator($pattern);
        $validator->validate($target);
        $this->assertTrue(true);
    }
    
    /**
     * @param $pattern
     * @param $target
     */
    #[DataProvider('getInvalidStrings')]
    public function testInvalidStrings($pattern, $target)
    {
        $validator = new RegexValidator($pattern);
        $this->expectException(RegexNotMatchedException::class);
        $validator->validate($target);
    }
    
    public static function getValidStrings()
    {
        return [
            ['/happy/', "happy new year"],
            ['/年好/u', "新年好!"],
            ['/[0-9]{3,}/', '123'],
        ];
    }
    
    public static function getInvalidStrings()
    {
        return [
            ['/dog/', 'cat'],
        ];
    }

    public function testInvalidPatternThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RegexValidator('invalid[pattern');
    }

    public function testNonStringTargetThrows(): void
    {
        $validator = new RegexValidator('/test/');
        $this->expectException(\Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException::class);
        $validator->validate(123);
    }
}
