<?php
declare(strict_types=1);

namespace Oasis\Mlib\Utils;

enum AnsiColor: int
{
    case Black        = 0;
    case Red          = 1;
    case Green        = 2;
    case Yellow       = 3;
    case Blue         = 4;
    case Magenta      = 5;
    case Cyan         = 6;
    case White        = 7;
    case LightBlack   = 100;
    case LightRed     = 101;
    case LightGreen   = 102;
    case LightYellow  = 103;
    case LightBlue    = 104;
    case LightMagenta = 105;
    case LightCyan    = 106;
    case LightWhite   = 107;
}
