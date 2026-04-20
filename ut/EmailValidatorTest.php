<?php
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Validators\EmailValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 21:56
 */
class EmailValidatorTest extends TestCase
{
    /**
     * @param $target
     */
    #[DataProvider('getValidEmails')]
    public function testValidEmails($target)
    {
        $validator = new EmailValidator();
        $validator->validate($target);
        $this->assertTrue(true);
    }
    
    /**
     * @param $target
     */
    #[DataProvider('getInvalidEmails')]
    public function testInvalidEmails($target)
    {
        $validator = new EmailValidator();
        $this->expectException(InvalidDataTypeException::class);
        $validator->validate($target);
    }
    
    public static function getValidEmails()
    {
        return [
            ['1001@xyz.com'],
            ['1001@xyz.com'],
            ['1001@123.com'],
            ['abc@xyz.com'],
            ['a/bc@xyz.com'],
            ['ab*c@xyz.com'],
            ['ab?c@xyz.com'],
            ['ab4c@xyz123.com'],
            ['a\'bc@xyz123.com'],
            ['ab4_c@xyz123.com'],
            ['ab~c@xyz-123.com'],
            ['ab4c@xyz-123.com'],
            ['a{b}c@xyz-123.com'],
            ['ab4c@xyz-123.com.cn'],
            ['ab4^c@xyz-123.com.cn'],
            ['ab4-c@xyz-123.com.cn'],
            ['ab4+c@xyz-123.com.cn'],
            ['ab4-c.min@xyz-123.com.cn'],
            ['ab4=c.min@xyz-123.com.cn'],
        ];
    }
    
    public static function getInvalidEmails()
    {
        return [
            [''],
            ['abc'],
            ['abc@dd@xyz.com'],
            ['abc#@xyz.com'],
            ['abc!@xyz.com'],
            ['a[bc@xyz.com'],
            ['abc]@xyz.com'],
            ['ab\\c@xyz.com'],
            ['ab(c@xyz.com'],
            ['ab)c@xyz.com'],
            //['a`bc@xyz.com'],
            ['ab>c@xyz.com'],
            ['ab<c@xyz.com'],
            ['a&bc@xyz123.com'],
            ['ab|c@xyz.com'],
            ['ab:c@xyz.com'],
            ['ab;c@xyz.com'],
            ['abc,@xyz.com'],
            ['abc"@xyz.com'],
            ['a$bc@xyz.com'],
            ['ab%c@xyz.com'],
            ['ab c@xyz.com'],
            ['abc@ab_c.com'],
        ];
    }
}
