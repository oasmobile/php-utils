<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 30/12/2017
 * Time: 11:42 PM
 */

namespace Oasis\Mlib\Utils;

class AnsiColorizer
{
    const CLOSE_TAG = "\e[0m";
    
    const COLOR_BLACK   = 0;
    const COLOR_RED     = 1;
    const COLOR_GREEN   = 2;
    const COLOR_YELLOW  = 3;
    const COLOR_BLUE    = 4;
    const COLOR_MAGENTA = 5;
    const COLOR_CYAN    = 6;
    const COLOR_WHITE   = 7;
    
    public static function bold($text)
    {
        return self::close("\e[1m$text");
    }
    
    public static function underline($text)
    {
        return self::close("\e[4m$text");
    }
    
    public static function reverse($text)
    {
        return self::close("\e[7m$text");
    }
    
    public static function foreground($text, $color)
    {
        $color = \strtoupper($color);
        if (StringUtils::stringStartsWith($color, 'LIGHT-')) {
            return self::bold(self::foreground($text, \substr($color, 6)));
        }
        if (!\is_numeric($color)) {
            $offset = @\constant(self::class . "::COLOR_" . $color);
            if ($offset === null) {
                // not supported color
                return $text;
            }
            $code = 30 + $offset;
        }
        else {
            $code = "38;5;$color";
        }
        
        return self::close("\e[{$code}m$text");
    }
    
    public static function background($text, $color)
    {
        $color = \strtoupper($color);
        if (StringUtils::stringStartsWith($color, 'LIGHT-')) {
            return self::bold(self::foreground($text, \substr($color, 6)));
        }
        if (!\is_numeric($color)) {
            $offset = @\constant(self::class . "::COLOR_" . $color);
            if ($offset === null) {
                // not supported color
                return $text;
            }
            $code = 40 + $offset;
        }
        else {
            $code = "48;5;$color";
        }
        
        return self::close("\e[{$code}m$text");
    }
    
    protected static function close($text)
    {
        return StringUtils::stringEndsWith($text, self::CLOSE_TAG)
            ? $text
            : $text . self::CLOSE_TAG;
    }
}
