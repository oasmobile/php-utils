<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-05-16
 * Time: 15:28
 */

namespace Oasis\Mlib\Utils\Exceptions;

class DataValidationException extends \RuntimeException
{
    protected string $fieldName;
    
    public static function create(string $message = "", int $code = 0, ?\Throwable $previous = null): static
    {
        return new static($message, $code, $previous);
    }
    
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    public function getFieldName(): string
    {
        return $this->fieldName;
    }
    
    public function setFieldName(string $fieldName): void
    {
        $this->fieldName = $fieldName;
    }
    
    public function withFieldName(string $fieldName): static
    {
        $this->fieldName = $fieldName;
        
        return $this;
    }
    
}
