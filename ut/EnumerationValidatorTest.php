<?php
use Oasis\Mlib\Utils\Exceptions\InvalidValueException;
use Oasis\Mlib\Utils\Validators\EnumerationValidator;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 22:16
 */
class EnumerationValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getValidEnumerations
     *
     * @param $target
     * @param $enumerations
     * @param $strict
     * @param $caseSensitive
     */
    public function testValidStrings($target, $enumerations, $strict, $caseSensitive)
    {
        $validator = new EnumerationValidator($enumerations, $strict, $caseSensitive);
        $validator->validate($target);
    }
    
    /**
     * @dataProvider getInvalidEnumerations
     *
     * @param $target
     * @param $enumerations
     * @param $strict
     * @param $caseSensitive
     */
    public function testInvalidStrings($target, $enumerations, $strict, $caseSensitive)
    {
        $validator = new EnumerationValidator($enumerations, $strict, $caseSensitive);
        $this->expectException(InvalidValueException::class);
        $validator->validate($target);
    }
    
    public function getValidEnumerations()
    {
        return [
            ['web', ['web', 'cli'], true, true],
            ['web', ['Web', 'cli'], true, false],
            ['123', [123, 'cli'], false, false],
        ];
    }
    
    public function getInvalidEnumerations()
    {
        return [
            ['web', ['web2', 'cli'], true, true],
            ['web', ['Web', 'cli'], true, true],
            ['123', [123, 'cli'], true, true],
        ];
    }
}
