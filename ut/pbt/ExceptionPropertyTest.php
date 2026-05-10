<?php
declare(strict_types=1);

use Eris\Generators;
use Eris\TestTrait;
use Oasis\Mlib\Utils\Exceptions\DataValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExceptionPropertyTest extends TestCase
{
    use TestTrait;

    private function allExceptionClasses(): array
    {
        return [
            DataValidationException::class,
            \Oasis\Mlib\Utils\Exceptions\DataEmptyException::class,
            \Oasis\Mlib\Utils\Exceptions\ExistenceViolationException::class,
            \Oasis\Mlib\Utils\Exceptions\InvalidArrayElementException::class,
            \Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException::class,
            \Oasis\Mlib\Utils\Exceptions\InvalidValueException::class,
            \Oasis\Mlib\Utils\Exceptions\MandatoryValueMissingException::class,
            \Oasis\Mlib\Utils\Exceptions\RegexNotMatchedException::class,
            \Oasis\Mlib\Utils\Exceptions\StringTooLongException::class,
            \Oasis\Mlib\Utils\Exceptions\StringTooShortException::class,
            \Oasis\Mlib\Utils\Exceptions\UniquenessViolationException::class,
        ];
    }

    // ─── Property safety (uninitialized access) ─────────────────────────

    #[Test]
    public function propertySafety(): void
    {
        $classes = $this->allExceptionClasses();

        $this->forAll(
            Generators::choose(0, count($classes) - 1),
            Generators::string(),
            Generators::int(),
        )->then(function (int $classIdx, string $message, int $code) use ($classes): void {
            $class = $classes[$classIdx];

            $e = new $class($message, $code);
            $fieldName = $e->getFieldName();
            $this->assertIsString($fieldName, "$class::getFieldName() must return string");
            $this->assertSame('', $fieldName, "$class::getFieldName() must default to ''");
        });
    }

    // ─── fieldName round-trip ───────────────────────────────────────────

    #[Test]
    public function fieldNameRoundTrip(): void
    {
        $classes = $this->allExceptionClasses();

        $this->forAll(
            Generators::choose(0, count($classes) - 1),
            Generators::string(),
            Generators::string(),
        )->then(function (int $classIdx, string $message, string $fieldName) use ($classes): void {
            $class = $classes[$classIdx];

            // via setFieldName
            $e1 = new $class($message);
            $e1->setFieldName($fieldName);
            $this->assertSame($fieldName, $e1->getFieldName());

            // via withFieldName (fluent)
            $e2 = new $class($message);
            $returned = $e2->withFieldName($fieldName);
            $this->assertSame($e2, $returned, "$class::withFieldName() must return \$this");
            $this->assertSame($fieldName, $e2->getFieldName());
        });
    }
}
