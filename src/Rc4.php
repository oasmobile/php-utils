<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 18:04
 */

namespace Oasis\Mlib\Utils;

class Rc4
{
    public static function rc4($key, $input)
    {
        $key = (string)$key;
        $s   = [];
        for ($i = 0; $i < 256; $i++) {
            $s[$i] = $i;
        }
        $j = 0;
        for ($i = 0; $i < 256; $i++) {
            $j     = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
            $x     = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $x;
        }
        $i   = 0;
        $j   = 0;
        $res = '';
        for ($y = 0; $y < strlen($input); $y++) {
            $i     = ($i + 1) % 256;
            $j     = ($j + $s[$i]) % 256;
            $x     = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $x;
            $res .= chr(ord($input[$y]) ^ $s[($s[$i] + $s[$j]) % 256]);
            //$res .= $input[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
        }

        return $res;
    }
}
