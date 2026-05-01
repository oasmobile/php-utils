<?php
declare(strict_types=1);
use Oasis\Mlib\Utils\StringUtils;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 18:18
 */
class StringUtilsTest extends TestCase
{
    public function testStringStartsWith()
    {
        $this->assertTrue(StringUtils::stringStartsWith("abcdef", "ab"));
        $this->assertFalse(StringUtils::stringStartsWith("abcdef", "cd"));
        $this->assertFalse(StringUtils::stringStartsWith("abcdef", "ef"));
        $this->assertTrue(StringUtils::stringStartsWith("abcdef", ""));
        $this->assertFalse(StringUtils::stringStartsWith("", "abcdef"));
    }

    public function testStringEndsWith()
    {
        $this->assertFalse(StringUtils::stringEndsWith("abcdef", "ab"));
        $this->assertFalse(StringUtils::stringEndsWith("abcdef", "cd"));
        $this->assertTrue(StringUtils::stringEndsWith("abcdef", "ef"));
        $this->assertTrue(StringUtils::stringEndsWith("abcdef", ""));
        $this->assertFalse(StringUtils::stringEndsWith("", "abcdef"));
    }

    public function testStringChopdown()
    {
        $str = "abcdefg";
        $this->assertEquals("abcd", StringUtils::stringChopdown($str, 4));

        $chinese = "中国人";
        $this->assertEquals("中", StringUtils::stringChopdown($chinese, 1));
        $this->assertNotEquals("中", StringUtils::stringChopdown($chinese, 1, true));
        $this->assertEquals("中", StringUtils::stringChopdown($chinese, 3, true));
    }
}
