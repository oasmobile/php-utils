<?php
declare(strict_types=1);

use Eris\Generators;
use Eris\TestTrait;
use Oasis\Mlib\Utils\Exceptions\DataValidationException;
use Oasis\Mlib\Utils\TrimDirection;
use Oasis\Mlib\Utils\Validators\ArrayValidator;
use Oasis\Mlib\Utils\Validators\BooleanValidator;
use Oasis\Mlib\Utils\Validators\ChainedValidator;
use Oasis\Mlib\Utils\Validators\DummyValidator;
use Oasis\Mlib\Utils\Validators\EmailValidator;
use Oasis\Mlib\Utils\Validators\EnumerationValidator;
use Oasis\Mlib\Utils\Validators\FloatValidator;
use Oasis\Mlib\Utils\Validators\IntegerValidator;
use Oasis\Mlib\Utils\Validators\ObjectValidator;
use Oasis\Mlib\Utils\Validators\RegexValidator;
use Oasis\Mlib\Utils\Validators\StringLengthValidator;
use Oasis\Mlib\Utils\Validators\StringValidator;
use Oasis\Mlib\Utils\Validators\TrimmedStringValidator;
use Oasis\Mlib\Utils\Validators\UrlValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ValidatorPropertyTest extends TestCase
{
    use TestTrait;

    private function safeValidate($validator, mixed $input): array
    {
        try {
            $value = $validator->validate($input);
            return ['ok' => true, 'value' => $value, 'exception' => null];
        } catch (DataValidationException $e) {
            return ['ok' => false, 'value' => null, 'exception' => get_class($e)];
        } catch (\TypeError $e) {
            return ['ok' => false, 'value' => null, 'exception' => 'TypeError'];
        }
    }

    private function assertValidatorDeterministic($validator, mixed $input, string $label): void
    {
        $r1 = $this->safeValidate($validator, $input);
        $r2 = $this->safeValidate($validator, $input);

        $this->assertSame($r1['ok'], $r2['ok'], "$label: determinism (ok)");
        if ($r1['ok']) {
            $this->assertEquals($r1['value'], $r2['value'], "$label: determinism (value)");
        } else {
            $this->assertSame($r1['exception'], $r2['exception'], "$label: determinism (exception type)");
        }
    }

    private function scalarGenerator(): \Eris\Generator
    {
        return Generators::oneOf(
            Generators::string(),
            Generators::int(),
            Generators::float(),
            Generators::bool(),
            Generators::constant(null),
        );
    }

    // ─── Determinism ────────────────────────────────────────────────────

    #[Test]
    public function behaviorPreservation(): void
    {
        $this->forAll($this->scalarGenerator())
            ->then(function (mixed $input): void {
                foreach ([true, false] as $strict) {
                    foreach ([true, false] as $allowEmpty) {
                        $v = new StringValidator($strict, $allowEmpty);
                        $this->assertValidatorDeterministic($v, $input, "StringValidator(strict=$strict,allowEmpty=$allowEmpty)");
                    }
                }

                foreach ([true, false] as $strict) {
                    foreach (TrimDirection::cases() as $dir) {
                        $v = new TrimmedStringValidator($strict, $dir);
                        $this->assertValidatorDeterministic($v, $input, "TrimmedStringValidator(strict=$strict,dir={$dir->name})");
                    }
                }

                foreach ([true, false] as $strict) {
                    $this->assertValidatorDeterministic(new IntegerValidator($strict), $input, "IntegerValidator(strict=$strict)");
                    $this->assertValidatorDeterministic(new FloatValidator($strict), $input, "FloatValidator(strict=$strict)");
                    $this->assertValidatorDeterministic(new BooleanValidator($strict), $input, "BooleanValidator(strict=$strict)");
                }

                foreach ([true, false] as $allowNull) {
                    $this->assertValidatorDeterministic(new ArrayValidator($allowNull), $input, "ArrayValidator(allowNull=$allowNull)");
                    $this->assertValidatorDeterministic(new ObjectValidator($allowNull), $input, "ObjectValidator(allowNull=$allowNull)");
                }

                $enumValues = ['a', 'b', 'c', 1, 2, true];
                foreach ([true, false] as $strictType) {
                    foreach ([true, false] as $caseSensitive) {
                        $v = new EnumerationValidator($enumValues, $strictType, $caseSensitive);
                        $this->assertValidatorDeterministic($v, $input, "EnumerationValidator(strictType=$strictType,caseSensitive=$caseSensitive)");
                    }
                }

                $this->assertValidatorDeterministic(new StringLengthValidator(100, 0, false), $input, "StringLengthValidator(max=100)");
                $this->assertValidatorDeterministic(new RegexValidator('/^.*$/'), $input, "RegexValidator(/^.*$/)");
                $this->assertValidatorDeterministic(new EmailValidator(), $input, "EmailValidator");
                $this->assertValidatorDeterministic(new UrlValidator(), $input, "UrlValidator");

                $this->assertSame($input, (new DummyValidator())->validate($input));
                $this->assertSame($input, (new ChainedValidator(new DummyValidator(), new DummyValidator()))->validate($input));
            });
    }

    // ─── TrimmedStringValidator correctness ─────────────────────────────

    #[Test]
    public function trimmedStringCorrectness(): void
    {
        $this->forAll(Generators::string())
            ->then(function (string $input): void {
                $this->assertSame(\trim($input), (new TrimmedStringValidator(false, TrimDirection::Both))->validate($input));
                $this->assertSame(\ltrim($input), (new TrimmedStringValidator(false, TrimDirection::Left))->validate($input));
                $this->assertSame(\rtrim($input), (new TrimmedStringValidator(false, TrimDirection::Right))->validate($input));
            });
    }

    // ─── Type preservation ──────────────────────────────────────────────

    #[Test]
    public function typePreservation(): void
    {
        $this->forAll($this->scalarGenerator())
            ->then(function (mixed $input): void {
                try {
                    $result = (new StringValidator(false))->validate($input);
                    $this->assertIsString($result, "StringValidator must return string");
                } catch (DataValidationException) {}

                try {
                    $result = (new IntegerValidator(false))->validate($input);
                    $this->assertIsInt($result, "IntegerValidator must return int");
                } catch (DataValidationException) {}

                try {
                    $result = (new FloatValidator(false))->validate($input);
                    $this->assertIsFloat($result, "FloatValidator must return float");
                } catch (DataValidationException) {}

                try {
                    $result = (new BooleanValidator(false))->validate($input);
                    $this->assertIsBool($result, "BooleanValidator must return bool");
                } catch (DataValidationException) {}
            });
    }

    // ─── Idempotence ────────────────────────────────────────────────────

    #[Test]
    public function idempotence(): void
    {
        $this->forAll($this->scalarGenerator())
            ->then(function (mixed $input): void {
                $validators = [
                    new StringValidator(false),
                    new StringValidator(false, false),
                    new IntegerValidator(false),
                    new FloatValidator(false),
                    new BooleanValidator(false),
                    new TrimmedStringValidator(false, TrimDirection::Both),
                    new TrimmedStringValidator(false, TrimDirection::Left),
                    new TrimmedStringValidator(false, TrimDirection::Right),
                    new StringLengthValidator(100, 0, true),
                    new DummyValidator(),
                ];

                foreach ($validators as $validator) {
                    try {
                        $first = $validator->validate($input);
                        $second = $validator->validate($first);
                        $this->assertEquals($first, $second,
                            get_class($validator) . ": validate(validate(x)) must equal validate(x)");
                    } catch (DataValidationException) {}
                }
            });
    }

    // ─── Composition equivalence ────────────────────────────────────────

    #[Test]
    public function chainedComposition(): void
    {
        $this->forAll(Generators::string())
            ->then(function (string $input): void {
                $trimmer = new TrimmedStringValidator(false, TrimDirection::Both);
                $lengthChecker = new StringLengthValidator(50, 0, true);
                $chained = new ChainedValidator($trimmer, $lengthChecker);

                try {
                    $manual = $lengthChecker->validate($trimmer->validate($input));
                } catch (DataValidationException $e) {
                    $manual = $e;
                }

                try {
                    $chainedResult = $chained->validate($input);
                } catch (DataValidationException $e) {
                    $chainedResult = $e;
                }

                if ($manual instanceof DataValidationException) {
                    $this->assertInstanceOf(DataValidationException::class, $chainedResult);
                } else {
                    $this->assertSame($manual, $chainedResult);
                }
            });
    }

    // ─── StringLengthValidator post-condition ────────────────────────────

    #[Test]
    public function stringLengthPostCondition(): void
    {
        $this->forAll(
            Generators::string(),
            Generators::choose(0, 20),
            Generators::choose(1, 100),
            Generators::bool(),
        )->then(function (string $input, int $minLen, int $maxLen, bool $chopDown): void {
            if ($minLen > $maxLen) [$minLen, $maxLen] = [$maxLen, $minLen];

            $validator = new StringLengthValidator($maxLen, $minLen, $chopDown);

            try {
                $result = $validator->validate($input);
                $resultLen = mb_strlen($result, 'UTF-8');
                $this->assertGreaterThanOrEqual($minLen, $resultLen);
                $this->assertLessThanOrEqual($maxLen, $resultLen);
            } catch (DataValidationException) {}
        });
    }

    // ─── Strict rejection ───────────────────────────────────────────────

    #[Test]
    public function strictRejectsWrongTypes(): void
    {
        $this->forAll($this->scalarGenerator())
            ->then(function (mixed $input): void {
                $rejected = 0;

                if (!is_string($input)) {
                    try { (new StringValidator(true))->validate($input); $this->fail("must reject"); }
                    catch (DataValidationException) { $rejected++; }
                }
                if (!is_int($input)) {
                    try { (new IntegerValidator(true))->validate($input); $this->fail("must reject"); }
                    catch (DataValidationException) { $rejected++; }
                }
                if (!is_float($input)) {
                    try { (new FloatValidator(true))->validate($input); $this->fail("must reject"); }
                    catch (DataValidationException) { $rejected++; }
                }
                if (!is_bool($input)) {
                    try { (new BooleanValidator(true))->validate($input); $this->fail("must reject"); }
                    catch (DataValidationException) { $rejected++; }
                }

                $this->assertGreaterThanOrEqual(3, $rejected);
            });
    }

    // ─── Enumeration correctness ────────────────────────────────────────

    #[Test]
    public function enumerationCorrectness(): void
    {
        $this->forAll(
            Generators::vector(5, Generators::string()),
            Generators::string(),
        )->then(function (array $allowed, string $input): void {
            $validator = new EnumerationValidator($allowed, false, true);

            if (in_array($input, $allowed, false)) {
                $this->assertSame($input, $validator->validate($input));
            } else {
                try {
                    $validator->validate($input);
                    $this->fail("must reject value not in list");
                } catch (\Oasis\Mlib\Utils\Exceptions\InvalidValueException) {}
            }
        });
    }

    // ─── Exception completeness ─────────────────────────────────────────

    #[Test]
    public function exceptionCompleteness(): void
    {
        $this->forAll($this->scalarGenerator())
            ->then(function (mixed $input): void {
                $validators = [
                    'StringValidator(strict)'   => new StringValidator(true),
                    'IntegerValidator(strict)'  => new IntegerValidator(true),
                    'FloatValidator(strict)'    => new FloatValidator(true),
                    'BooleanValidator(strict)'  => new BooleanValidator(true),
                    'ArrayValidator'            => new ArrayValidator(),
                    'ObjectValidator'           => new ObjectValidator(),
                    'EmailValidator'            => new EmailValidator(),
                    'UrlValidator'              => new UrlValidator(),
                    'RegexValidator'            => new RegexValidator('/^IMPOSSIBLE_PATTERN_XYZ$/'),
                    'StringLengthValidator'     => new StringLengthValidator(1, 0, true),
                    'EnumerationValidator'      => new EnumerationValidator(['NEVER_MATCH_VALUE_XYZ'], true),
                ];

                foreach ($validators as $label => $validator) {
                    try {
                        $validator->validate($input);
                    } catch (DataValidationException $e) {
                        $this->assertIsString($e->getFieldName(), "$label: fieldName must be string");
                        $this->assertNotEmpty($e->getMessage(), "$label: message must be non-empty");
                    } catch (\TypeError) {}
                }
            });
    }
}
