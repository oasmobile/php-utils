<?php
use Oasis\Mlib\Utils\Rc4;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 18:06
 */
class Rc4Test extends TestCase
{
    public function testOneWayEncryption()
    {
        $str    = "abc";
        $key    = "xyz";
        $result = Rc4::rc4($key, $str);
        $this->assertEquals("\xfc\x19\x49", $result);
    }

    public function testEncryptionAndDecryption()
    {
        $key = mt_rand(1, 1000);
        $str = "abcdefg";
        $this->assertEquals($str, Rc4::rc4($key, Rc4::rc4($key, $str)));
    }
}
