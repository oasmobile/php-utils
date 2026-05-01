<?php
declare(strict_types=1);

namespace Oasis\Mlib\Utils;

class AnsiColorizer
{
    private const CLOSE_TAG = "\e[0m";

    public static function bold(string $text): string
    {
        return self::close("\e[1m$text");
    }

    public static function underline(string $text): string
    {
        return self::close("\e[4m$text");
    }

    public static function reverse(string $text): string
    {
        return self::close("\e[7m$text");
    }

    public static function foreground(string $text, AnsiColor|int $color): string
    {
        if ($color instanceof AnsiColor) {
            $baseColor = self::getBaseColor($color);
            if ($baseColor !== null) {
                return self::bold(self::foreground($text, $baseColor));
            }
            $code = 30 + $color->value;
        } else {
            $code = "38;5;{$color}";
        }

        return self::close("\e[{$code}m{$text}");
    }

    public static function background(string $text, AnsiColor|int $color): string
    {
        if ($color instanceof AnsiColor) {
            $baseColor = self::getBaseColor($color);
            if ($baseColor !== null) {
                return self::bold(self::background($text, $baseColor));
            }
            $code = 40 + $color->value;
        } else {
            $code = "48;5;{$color}";
        }

        return self::close("\e[{$code}m{$text}");
    }

    private static function getBaseColor(AnsiColor $color): ?AnsiColor
    {
        return match ($color) {
            AnsiColor::LightBlack   => AnsiColor::Black,
            AnsiColor::LightRed     => AnsiColor::Red,
            AnsiColor::LightGreen   => AnsiColor::Green,
            AnsiColor::LightYellow  => AnsiColor::Yellow,
            AnsiColor::LightBlue    => AnsiColor::Blue,
            AnsiColor::LightMagenta => AnsiColor::Magenta,
            AnsiColor::LightCyan    => AnsiColor::Cyan,
            AnsiColor::LightWhite   => AnsiColor::White,
            default                 => null,
        };
    }

    protected static function close(string $text): string
    {
        return str_ends_with($text, self::CLOSE_TAG)
            ? $text
            : $text . self::CLOSE_TAG;
    }
}
