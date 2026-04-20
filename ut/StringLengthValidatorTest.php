<?php
use Oasis\Mlib\Utils\Exceptions\StringTooLongException;
use Oasis\Mlib\Utils\Exceptions\StringTooShortException;
use Oasis\Mlib\Utils\Validators\StringLengthValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 22:10
 */
class StringLengthValidatorTest extends TestCase
{
    /**
     * @param $target
     */
    #[DataProvider('getValidStrings')]
    public function testValidStrings($target)
    {
        $validator = new StringLengthValidator(5);
        $validator->validate($target);
        $this->assertTrue(true);
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getInvalidStrings')]
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
    
    public static function getValidStrings()
    {
        return [
            ['abcde'],
            ['abcd'],
            ['哈哈哈哈哈'],
        ];
    }
    
    public static function getInvalidStrings()
    {
        return [
            [''],
            ['abcdef'],
            ['啊哈哈哈哈哈'],
        ];
    }
}
