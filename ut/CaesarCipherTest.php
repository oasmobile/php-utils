<?php
declare(strict_types=1);
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

    public function testEmptyStringCipher()
    {
        $cipher    = new CaesarCipher();
        $encrypted = $cipher->encrypt('');
        $this->assertEquals('', $encrypted);
        $decrypted = $cipher->decrypt('');
        $this->assertEquals('', $decrypted);
    }

    public function testGetters()
    {
        $cipher = new CaesarCipher(64, 8, 10);
        $this->assertEquals(64, $cipher->getBits());
        $this->assertEquals(8, $cipher->getPartition());
        $this->assertEquals(10, $cipher->getStrength());
    }

    public function testInvalidPartitionOdd()
    {
        $this->expectException(\InvalidArgumentException::class);
        new CaesarCipher(32, 7);
    }

    public function testInvalidBitsZero()
    {
        $this->expectException(\InvalidArgumentException::class);
        new CaesarCipher(0);
    }

    public function testInvalidBitsOdd()
    {
        $this->expectException(\InvalidArgumentException::class);
        new CaesarCipher(31);
    }

    public function testInvalidBitsTooLarge()
    {
        $this->expectException(\InvalidArgumentException::class);
        new CaesarCipher(128);
    }

    public function testInvalidBitsNotDivisibleByPartition()
    {
        $this->expectException(\InvalidArgumentException::class);
        new CaesarCipher(32, 6);
    }

    public function testEncryptDecryptWithZero()
    {
        $cipher    = new CaesarCipher();
        $encrypted = $cipher->encrypt(0);
        $decrypted = $cipher->decrypt($encrypted);
        $this->assertEquals(0, $decrypted);
    }

    public function testEncryptStringWithNonByteAlignedBits(): void
    {
        // bits=30 is not divisible by 8, should throw when encrypting string
        $cipher = new CaesarCipher(30, 6, 5);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("divisable by 8");
        $cipher->encrypt("hello");
    }

    public function testDecryptStringWithNonByteAlignedBits(): void
    {
        // bits=30 is not divisible by 8, should throw when decrypting string
        $cipher = new CaesarCipher(30, 6, 5);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("divisable by 8");
        $cipher->decrypt("hello");
    }

    public function testDecryptMalformedStringHeader(): void
    {
        $cipher = new CaesarCipher();
        // Create a string with compensation byte > PHP_INT_SIZE
        $malformed = pack('C', PHP_INT_SIZE + 1) . str_repeat("\x00", 4);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Malformed string header");
        $cipher->decrypt($malformed);
    }

    public function testDecryptWithoutPriorEncrypt(): void
    {
        // Call decrypt on a fresh cipher to trigger reverseLookup's prepareLookupTable path
        $cipher = new CaesarCipher();
        $result = $cipher->decrypt(12345);
        // Just verify it returns an integer (the lookup table is random, so we can't predict the value)
        $this->assertIsInt($result);
    }
}
