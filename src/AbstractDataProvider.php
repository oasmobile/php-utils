<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-09-15
 * Time: 11:48
 */

namespace Oasis\Mlib\Utils;

use Oasis\Mlib\Utils\Exceptions\DataValidationException;
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
    public function has(string $key, ValidatorInterface|DataType $validator = DataType::Mixed): bool
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
    
    public function get(string $key, ValidatorInterface|DataType $validator = DataType::String, bool $isMandatory = false, mixed $default = null): mixed
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
    
    public function getMandatory(string $key, ValidatorInterface|DataType $validator = DataType::String): mixed
    {
        return $this->get($key, $validator, true);
    }
    
    public function getOptional(string $key, ValidatorInterface|DataType $validator = DataType::String, mixed $default = null): mixed
    {
        return $this->get($key, $validator, false, $default);
    }
    
    /**
     * @param string $key the key to be used to read a value from the data provider
     *
     * @return mixed|null null indicates the value is not presented in the data provider
     */
    abstract protected function getValue(string $key): mixed;
    
    protected function getValidatorByLegacyString(DataType $type): ValidatorInterface
    {
        return match ($type) {
            DataType::String         => new StringValidator(),
            DataType::NonEmptyString => new StringValidator(false, false),
            DataType::TrimmedString  => new TrimmedStringValidator(false),
            DataType::Int            => new IntegerValidator(),
            DataType::Float          => new FloatValidator(),
            DataType::Bool           => new BooleanValidator(),
            DataType::Object         => new ObjectValidator(),
            DataType::Mixed          => new DummyValidator(),
            DataType::Array2D        => new Array2DValidator(),
            DataType::Array          => new ArrayValidator(),
        };
    }
    
}
