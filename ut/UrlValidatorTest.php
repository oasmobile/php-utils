<?php
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Validators\UrlValidator;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-02
 * Time: 21:56
 */
class UrlValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getValidUrls
     *
     * @param $target
     */
    public function testValidUrls($target)
    {
        $validator = new UrlValidator();
        $validator->validate($target);
    }
    
    /**
     * @dataProvider getInvalidUrls
     * @param $target
     */
    public function testInvalidUrls($target)
    {
        $validator = new UrlValidator();
        $this->expectException(InvalidDataTypeException::class);
        $validator->validate($target);
    }
    
    public function getValidUrls()
    {
        return [
            ['http://baidu.com'],
            ['http://163.com'],
            ['http://img.163.com'],
            ['http://img.1-63.com'],
            ['http://baidu.com/'],
            ['https://baidu.com/'],
            ['http://baidu.com/abc'],
            ['http://baidu.com/abc.php'],
            ['http://baidu.com/abc.php?name=8'],
            ['http://baidu.com/abc.php?name=8&ab=9'],
            ['http://baidu.com/abc.php?name=8&ab=%20l%20'],
            ['http://baidu.com/abc.php?name=8&ab=%20l%20#ao=8'],
        ];
    }
    
    public function getInvalidUrls()
    {
        return [
            ['baidu.com'], // scheme required
            ['http://_baidu.com'],
        ];
    }
}
