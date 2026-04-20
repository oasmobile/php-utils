<?php
use Oasis\Mlib\Utils\CaesarCipher;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-08-08
 * Time: 16:27
 */
class CaesarCipherTest extends TestCase
{
    public function testNormalCipher()
    {
        $cipher    = new CaesarCipher();
        $result    = $cipher->encrypt(1);
        $decrypted = $cipher->decrypt($result);
        
        $this->assertEquals(1, $decrypted);
        
        $cipher    = new CaesarCipher(64, 8, 10);
        $result    = $cipher->encrypt(1);
        $decrypted = $cipher->decrypt($result);
        
        $this->assertEquals(1, $decrypted);
        
        $cipher    = new CaesarCipher(30, 6, 10);
        $result    = $cipher->encrypt(1);
        $decrypted = $cipher->decrypt($result);
        
        $this->assertEquals(1, $decrypted);
    }
    
    public function testSavedLookupTable()
    {
        $cipher = new CaesarCipher();
        $table  = $cipher->getLookupTable();
        $result = $cipher->encrypt(1);
        
        $cipher = new CaesarCipher();
        $cipher->setLookupTable($table);
        $result2 = $cipher->encrypt(1);
        
        $this->assertEquals($result, $result2);
        
        $cipher = new CaesarCipher(64, 8, 10);
        $table  = $cipher->getLookupTable();
        $result = $cipher->encrypt(1);
        
        $cipher = new CaesarCipher(64, 8, 10);
        $cipher->setLookupTable($table);
        $result2 = $cipher->encrypt(1);
        
        $this->assertEquals($result, $result2);
    }
    
    public function testSequentialNumbers()
    {
        $cipher = new CaesarCipher();
        for ($i = 0; $i < 2000; ++$i) {
            $result    = $cipher->encrypt($i);
            $decrypted = $cipher->decrypt($result);
            
            $this->assertEquals($i, $decrypted);
        }
    }
    
    public function testStringCipher()
    {
        $cipher    = new CaesarCipher();
        $str       = "abcdefghijklmnopqrstuvwxyz";
        $encrypted = $cipher->encrypt($str);
        $decrypted = $cipher->decrypt($encrypted);
        
        $this->assertEquals($str, $decrypted);
        
        $cipher    = new CaesarCipher(64);
        $str       = "abcdefghijklmnopqrstuvwxyz0123456789";
        $encrypted = $cipher->encrypt($str);
        $decrypted = $cipher->decrypt($encrypted);
        
        $this->assertEquals($str, $decrypted);
        
        $cipher    = new CaesarCipher(64, 4);
        $str       = "abcdefghijklmnopqrstuvwxyz0123456789";
        $encrypted = $cipher->encrypt($str);
        $decrypted = $cipher->decrypt($encrypted);
        
        $this->assertEquals($str, $decrypted);
        
        $cipher    = new CaesarCipher(64, 4, 12);
        $str       = "abcdefghijklmnopqrstuvwxyz0123456789";
        $encrypted = $cipher->encrypt($str);
        $decrypted = $cipher->decrypt($encrypted);
        
        $this->assertEquals($str, $decrypted);
    }
}
