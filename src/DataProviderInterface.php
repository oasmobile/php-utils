<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-02-15
 * Time: 10:55
 */

namespace Oasis\Mlib\Utils;

interface DataProviderInterface
{
    const INT_TYPE              = "requireInt";
    const FLOAT_TYPE            = "requireFloat";
    const STRING_TYPE           = "requireString";
    const NON_EMPTY_STRING_TYPE = "requireNonEmptyString";
    const TRIMMED_STRING_TYPE   = "requireTrimmedString";
    const ARRAY_TYPE            = "requireArray";
    const ARRAY_2D_TYPE         = "requireArray2D";
    const BOOL_TYPE             = "requireBool";
    const OBJECT_TYPE           = "requireObject";
    const MIXED_TYPE            = "requireMixed";
    
    public function has($key, $validator = self::MIXED_TYPE);
    
    public function get($key, $validator = self::STRING_TYPE, $isMandatory = false, $default = null);
    
    public function getMandatory($key, $validator = self::STRING_TYPE);
    
    public function getOptional($key, $validator = self::STRING_TYPE, $default = null);
}
