<?php
use Oasis\Mlib\Utils\Exceptions\RegexNotMatchedException;
use Oasis\Mlib\Utils\Validators\RegexValidator;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 22:16
 */
class RegexValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getValidStrings
     *
     * @param $pattern
     * @param $target
     */
    public function testValidStrings($pattern, $target)
    {
        $validator = new RegexValidator($pattern);
        $validator->validate($target);
    }
    
    /**
     * @dataProvider getInvalidStrings
     *
     * @param $pattern
     * @param $target
     */
    public function testInvalidStrings($pattern, $target)
    {
        $validator = new RegexValidator($pattern);
        $this->expectException(RegexNotMatchedException::class);
        $validator->validate($target);
    }
    
    public function getValidStrings()
    {
        return [
            ['/happy/', "happy new year"],
            ['/年好/u', "新年好!"],
            ['/[0-9]{3,}/', '123'],
        ];
    }
    
    public function getInvalidStrings()
    {
        return [
            ['/dog/', 'cat'],
        ];
    }
}
