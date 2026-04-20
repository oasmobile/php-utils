<?php
use Oasis\Mlib\Utils\Exceptions\InvalidValueException;
use Oasis\Mlib\Utils\Validators\EnumerationValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 22:16
 */
class EnumerationValidatorTest extends TestCase
{
    /**
     * @param $target
     * @param $enumerations
     * @param $strict
     * @param $caseSensitive
     */
    #[DataProvider('getValidEnumerations')]
    public function testValidStrings($target, $enumerations, $strict, $caseSensitive)
    {
        $validator = new EnumerationValidator($enumerations, $strict, $caseSensitive);
        $validator->validate($target);
        $this->assertTrue(true);
    }
    
    /**
     * @param $target
     * @param $enumerations
     * @param $strict
     * @param $caseSensitive
     */
    #[DataProvider('getInvalidEnumerations')]
    public function testInvalidStrings($target, $enumerations, $strict, $caseSensitive)
    {
        $validator = new EnumerationValidator($enumerations, $strict, $caseSensitive);
        $this->expectException(InvalidValueException::class);
        $validator->validate($target);
    }
    
    public static function getValidEnumerations()
    {
        return [
            ['web', ['web', 'cli'], true, true],
            ['web', ['Web', 'cli'], true, false],
            ['123', [123, 'cli'], false, false],
        ];
    }
    
    public static function getInvalidEnumerations()
    {
        return [
            ['web', ['web2', 'cli'], true, true],
            ['web', ['Web', 'cli'], true, true],
            ['123', [123, 'cli'], true, true],
        ];
    }
}
