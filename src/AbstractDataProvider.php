<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-09-15
 * Time: 11:48
 */

namespace Oasis\Mlib\Utils;

use Oasis\Mlib\Utils\Exceptions\DataValidationException;
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Exceptions\MandatoryValueMissingException;
use Oasis\Mlib\Utils\Validators\Array2DValidator;
use Oasis\Mlib\Utils\Validators\ArrayValidator;
use Oasis\Mlib\Utils\Validators\BooleanValidator;
use Oasis\Mlib\Utils\Validators\DummyValidator;
use Oasis\Mlib\Utils\Validators\FloatValidator;
use Oasis\Mlib\Utils\Validators\IntegerValidator;
use Oasis\Mlib\Utils\Validators\ObjectValidator;
use Oasis\Mlib\Utils\Validators\StringValidator;
use Oasis\Mlib\Utils\Validators\TrimmedStringValidator;
use Oasis\Mlib\Utils\Validators\ValidatorInterface;

abstract class AbstractDataProvider implements DataProviderInterface
{
    public function has($key, $validator = self::MIXED_TYPE)
    {
        $value = $this->getValue($key);
        if ($value === null) {
            return false;
        }
        
        if (!$validator instanceof ValidatorInterface) {
            $validator = $this->getValidatorByLegacyString($validator);
        }
        
        try {
            $value = $validator->validate($value);
            
            return ($value !== null);
        } catch (DataValidationException $e) {
            return false;
        }
    }
    
    /**
     * @param string                    $key
     * @param ValidatorInterface|string $validator
     * @param bool|false                $isMandatory
     * @param null                      $default
     *
     * @return mixed
     * @throws InvalidDataTypeException
     * @throws MandatoryValueMissingException
     */
    public function get($key, $validator = self::STRING_TYPE, $isMandatory = false, $default = null)
    {
        $value = $this->getValue($key);
        
        if ($value === null) {
            if ($isMandatory) {
                throw (new MandatoryValueMissingException("Mandatory value $key is missing in data"))
                    ->withFieldName($key);
            }
            else {
                return $default;
            }
        }
        
        try {
            if (!$validator instanceof ValidatorInterface) {
                $validator = $this->getValidatorByLegacyString($validator);
            }
            $value = $validator->validate($value);
            
            return $value;
        } catch (DataValidationException $e) {
            $e->setFieldName($key);
            throw $e;
        }
    }
    
    public function getMandatory($key, $type = self::STRING_TYPE)
    {
        return $this->get($key, $type, true);
    }
    
    public function getOptional($key, $type = self::STRING_TYPE, $default = null)
    {
        return $this->get($key, $type, false, $default);
    }
    
    /**
     * @param string $key the key to be used to read a value from the data provider
     *
     * @return mixed|null       null indicates the value is not presented in the data provider
     */
    abstract protected function getValue($key);
    
    protected function getValidatorByLegacyString($type)
    {
        switch ($type) {
            case self::STRING_TYPE:
                return new StringValidator();
                break;
            case self::NON_EMPTY_STRING_TYPE:
                return new StringValidator(false, false);
                break;
            case self::TRIMMED_STRING_TYPE:
                return new TrimmedStringValidator(false);
                break;
            case self::INT_TYPE:
                return new IntegerValidator();
                break;
            case self::FLOAT_TYPE:
                return new FloatValidator();
                break;
            case self::BOOL_TYPE:
                return new BooleanValidator();
                break;
            case self::OBJECT_TYPE:
                return new ObjectValidator();
                break;
            case self::MIXED_TYPE:
                return new DummyValidator();
                break;
            case self::ARRAY_2D_TYPE:
                return new Array2DValidator();
                break;
            case self::ARRAY_TYPE:
                return new ArrayValidator();
                break;
            default:
                throw new InvalidDataTypeException("Validator type '$type' is not an allowed type");
        }
    }
    
}
