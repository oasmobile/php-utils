<?php
use Oasis\Mlib\Utils\Exceptions\StringTooLongException;
use Oasis\Mlib\Utils\Exceptions\StringTooShortException;
use Oasis\Mlib\Utils\Validators\StringLengthValidator;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 22:10
 */
class StringLengthValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getValidStrings
     *
     * @param $target
     */
    public function testValidStrings($target)
    {
        $validator = new StringLengthValidator(5);
        $validator->validate($target);
    }
    
    /**
     * @dataProvider getInvalidStrings
     *
     * @param $target
     */
    public function testInvalidStrings($target)
    {
        $validator = new StringLengthValidator(5, 1);
        try {
            $validator->validate($target);
        } catch (Exception $e) {
            $this->assertTrue(
                ($e instanceof StringTooShortException)
                || ($e instanceof StringTooLongException)
            );
        }
    }
    
    public function testChopDown()
    {
        $validator = new StringLengthValidator(5, 1, true);
        $result    = $validator->validate('abcdefg');
        $this->assertEquals('abcde', $result);
        $result    = $validator->validate('甲乙丙丁戊己');
        $this->assertEquals('甲乙丙丁戊', $result);
    }
    
    public function getValidStrings()
    {
        return [
            ['abcde'],
            ['abcd'],
            ['哈哈哈哈哈'],
        ];
    }
    
    public function getInvalidStrings()
    {
        return [
            [''],
            ['abcdef'],
            ['啊哈哈哈哈哈'],
        ];
    }
}
