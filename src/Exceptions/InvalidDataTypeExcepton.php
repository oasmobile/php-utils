<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-09-15
 * Time: 11:51
 */
namespace Oasis\Mlib\Utils\Exceptions;

use Exception;
use RuntimeException;

class InvalidDataTypeException extends RuntimeException
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}
