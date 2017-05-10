<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-05-16
 * Time: 15:28
 */

namespace Oasis\Mlib\Utils\Exceptions;

class DataValidationException extends \RuntimeException
{
    /**
     * @var string
     */
    protected $fieldName;
    
    public static function create($message = "", $code = 0, \Exception $previous = null)
    {
        return new static($message, $code, $previous);
    }
    
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }
    
    /**
     * @param string $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }
    
    public function withFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
        
        return $this;
    }
    
}
