<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 18:03
 */

namespace Oasis\Mlib\Utils;

use voku\helper\UTF8;

class StringUtils
{
    /**
     * Chops down a string according to given max length, all characters beyond $maxLength is removed.
     *
     * This function is UTF8 compatible
     */
    public static function stringChopdown(string $str, int $maxLength, bool $lengthUnitInByte = false): string
    {
        if ($lengthUnitInByte) {
            return substr($str, 0, $maxLength);
        }

        $str = UTF8::to_utf8($str);
        $len = UTF8::strlen($str);
        if ($len <= $maxLength) {
            return $str;
        }

        return UTF8::substr($str, 0, $maxLength);
    }

    public static function stringStartsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    public static function stringEndsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }
}
