<?php

namespace Oasis\Mlib\Utils;

enum DataType: string
{
    case Int              = 'requireInt';
    case Float            = 'requireFloat';
    case String           = 'requireString';
    case NonEmptyString   = 'requireNonEmptyString';
    case TrimmedString    = 'requireTrimmedString';
    case Array            = 'requireArray';
    case Array2D          = 'requireArray2D';
    case Bool             = 'requireBool';
    case Object           = 'requireObject';
    case Mixed            = 'requireMixed';
}
