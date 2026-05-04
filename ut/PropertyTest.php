<?php
declare(strict_types=1);
/**
 * Property-Based Tests using Eris
 *
 * Replaces manual random generation with Eris generators and shrinking.
 */

use Eris\Generators;
use Eris\TestTrait;
use Oasis\Mlib\Utils\AnsiColor;
use Oasis\Mlib\Utils\AnsiColorizer;
use Oasis\Mlib\Utils\ArrayDataProvider;
use Oasis\Mlib\Utils\CaesarCipher;
use Oasis\Mlib\Utils\DataPacker;
use Oasis\Mlib\Utils\DataType;
use Oasis\Mlib\Utils\Exceptions\DataValidationException;
use Oasis\Mlib\Utils\Rc4;
use Oasis\Mlib\Utils\StringUtils;
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

class PropertyTest extends TestCase
{
    use TestTrait;

    // ─── Helpers ────────────────────────────────────────────────────────

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

    // ════════════════════════════════════════════════════════════════════
    // Property 1: Validator behavior preservation (determinism)
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function property1_validatorBehaviorPreservation(): void
    {
        $this->forAll($this->scalarGenerator())
            ->then(function (mixed $input): void {
                // StringValidator
                foreach ([true, false] as $strict) {
                    foreach ([true, false] as $allowEmpty) {
                        $v = new StringValidator($strict, $allowEmpty);
                        $this->assertValidatorDeterministic($v, $input, "StringValidator(strict=$strict,allowEmpty=$allowEmpty)");
                    }
                }

                // TrimmedStringValidator
                foreach ([true, false] as $strict) {
                    foreach (TrimDirection::cases() as $dir) {
                        $v = new TrimmedStringValidator($strict, $dir);
                        $this->assertValidatorDeterministic($v, $input, "TrimmedStringValidator(strict=$strict,dir={$dir->name})");
                    }
                }

                // IntegerValidator
                foreach ([true, false] as $strict) {
                    $this->assertValidatorDeterministic(new IntegerValidator($strict), $input, "IntegerValidator(strict=$strict)");
                }

                // FloatValidator
                foreach ([true, false] as $strict) {
                    $this->assertValidatorDeterministic(new FloatValidator($strict), $input, "FloatValidator(strict=$strict)");
                }

                // BooleanValidator
                foreach ([true, false] as $strict) {
                    $this->assertValidatorDeterministic(new BooleanValidator($strict), $input, "BooleanValidator(strict=$strict)");
                }

                // ArrayValidator
                foreach ([true, false] as $allowNull) {
                    $this->assertValidatorDeterministic(new ArrayValidator($allowNull), $input, "ArrayValidator(allowNull=$allowNull)");
                }

                // ObjectValidator
                foreach ([true, false] as $allowNull) {
                    $this->assertValidatorDeterministic(new ObjectValidator($allowNull), $input, "ObjectValidator(allowNull=$allowNull)");
                }

                // EnumerationValidator
                $enumValues = ['a', 'b', 'c', 1, 2, true];
                foreach ([true, false] as $strictType) {
                    foreach ([true, false] as $caseSensitive) {
                        $v = new EnumerationValidator($enumValues, $strictType, $caseSensitive);
                        $this->assertValidatorDeterministic($v, $input, "EnumerationValidator(strictType=$strictType,caseSensitive=$caseSensitive)");
                    }
                }

                // StringLengthValidator
                $this->assertValidatorDeterministic(new StringLengthValidator(100, 0, false), $input, "StringLengthValidator(max=100)");

                // RegexValidator
                $this->assertValidatorDeterministic(new RegexValidator('/^.*$/'), $input, "RegexValidator(/^.*$/)");

                // EmailValidator / UrlValidator
                $this->assertValidatorDeterministic(new EmailValidator(), $input, "EmailValidator");
                $this->assertValidatorDeterministic(new UrlValidator(), $input, "UrlValidator");

                // DummyValidator — pass-through
                $this->assertSame($input, (new DummyValidator())->validate($input));

                // ChainedValidator(Dummy, Dummy) — pass-through
                $this->assertSame($input, (new ChainedValidator(new DummyValidator(), new DummyValidator()))->validate($input));
            });
    }

    // ════════════════════════════════════════════════════════════════════
    // Property 1b: TrimmedStringValidator correctness
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function property1b_trimmedStringValidatorCorrectness(): void
    {
        $this->forAll(Generators::string())
            ->then(function (string $input): void {
                $this->assertSame(\trim($input), (new TrimmedStringValidator(false, TrimDirection::Both))->validate($input));
                $this->assertSame(\ltrim($input), (new TrimmedStringValidator(false, TrimDirection::Left))->validate($input));
                $this->assertSame(\rtrim($input), (new TrimmedStringValidator(false, TrimDirection::Right))->validate($input));
            });
    }

    // ════════════════════════════════════════════════════════════════════
    // Property 2: DataProvider behavior preservation
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function property2_dataProviderBehaviorPreservation(): void
    {
        $this->forAll(
            Generators::int(),
            Generators::float(),
            Generators::string(),
            Generators::bool(),
        )->then(function (int $intVal, float $floatVal, string $strVal, bool $boolVal): void {
            $data = [
                'intVal'   => $intVal,
                'floatVal' => $floatVal,
                'strVal'   => $strVal,
                'boolVal'  => $boolVal,
                'arrVal'   => [$intVal, $intVal + 1],
                'objVal'   => (object)['a' => 1],
                'nested'   => ['child' => ['deep' => $intVal]],
            ];

            $dp = new ArrayDataProvider($data);

            // has() determinism across all DataType cases
            foreach (DataType::cases() as $dataType) {
                foreach (['intVal', 'floatVal', 'strVal', 'boolVal'] as $key) {
                    $h1 = $dp->has($key, $dataType);
                    $h2 = $dp->has($key, $dataType);
                    $this->assertSame($h1, $h2, "has() determinism for key=$key, type={$dataType->name}");
                }

                // Missing key
                $this->assertFalse($dp->has('nonexistent_key', $dataType));
            }

            // Hierarchical path resolution
            $this->assertSame($intVal, $dp->getOptional('nested.child.deep', DataType::Int));
        });
    }

    // ════════════════════════════════════════════════════════════════════
    // Property 3: AnsiColorizer output correctness
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function property3_ansiColorizerOutputCorrectness(): void
    {
        $closeTag = "\e[0m";

        $basicColors = [
            AnsiColor::Black, AnsiColor::Red, AnsiColor::Green, AnsiColor::Yellow,
            AnsiColor::Blue, AnsiColor::Magenta, AnsiColor::Cyan, AnsiColor::White,
        ];
        $lightColors = [
            AnsiColor::LightBlack, AnsiColor::LightRed, AnsiColor::LightGreen, AnsiColor::LightYellow,
            AnsiColor::LightBlue, AnsiColor::LightMagenta, AnsiColor::LightCyan, AnsiColor::LightWhite,
        ];

        $this->forAll(Generators::string(), Generators::choose(0, 255))
            ->then(function (string $text, int $intColor) use ($closeTag, $basicColors, $lightColors): void {
                if ($text === '') {
                    $text = 'x';
                }

                // Basic colors: foreground & background
                foreach ($basicColors as $color) {
                    $fg = AnsiColorizer::foreground($text, $color);
                    $this->assertStringContainsString("\e[" . (30 + $color->value) . "m", $fg);
                    $this->assertStringEndsWith($closeTag, $fg);
                    $this->assertStringContainsString($text, $fg);

                    $bg = AnsiColorizer::background($text, $color);
                    $this->assertStringContainsString("\e[" . (40 + $color->value) . "m", $bg);
                    $this->assertStringEndsWith($closeTag, $bg);
                    $this->assertStringContainsString($text, $bg);
                }

                // Light colors: bold-wrapped
                foreach ($lightColors as $color) {
                    $fg = AnsiColorizer::foreground($text, $color);
                    $this->assertStringContainsString("\e[1m", $fg);
                    $this->assertStringEndsWith($closeTag, $fg);

                    $bg = AnsiColorizer::background($text, $color);
                    $this->assertStringContainsString("\e[1m", $bg);
                    $this->assertStringEndsWith($closeTag, $bg);
                }

                // 256-color mode
                $fg256 = AnsiColorizer::foreground($text, $intColor);
                $this->assertStringContainsString("38;5;{$intColor}", $fg256);
                $this->assertStringEndsWith($closeTag, $fg256);

                $bg256 = AnsiColorizer::background($text, $intColor);
                $this->assertStringContainsString("48;5;{$intColor}", $bg256);
                $this->assertStringEndsWith($closeTag, $bg256);
            });
    }

    // ════════════════════════════════════════════════════════════════════
    // Property 4: StringUtils function equivalence
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function property4_stringUtilsFunctionEquivalence(): void
    {
        $this->forAll(Generators::string(), Generators::string())
            ->then(function (string $haystack, string $needle): void {
                $this->assertSame(
                    str_starts_with($haystack, $needle),
                    StringUtils::stringStartsWith($haystack, $needle),
                );
                $this->assertSame(
                    str_ends_with($haystack, $needle),
                    StringUtils::stringEndsWith($haystack, $needle),
                );
            });
    }

    // ════════════════════════════════════════════════════════════════════
    // Property 5: CaesarCipher encrypt/decrypt round-trip
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function property5_caesarCipherRoundTrip(): void
    {
        $validConfigs = [
            ['bits' => 8,  'partition' => 2, 'strength' => 5],
            ['bits' => 8,  'partition' => 4, 'strength' => 5],
            ['bits' => 8,  'partition' => 8, 'strength' => 5],
            ['bits' => 16, 'partition' => 4, 'strength' => 5],
            ['bits' => 16, 'partition' => 8, 'strength' => 5],
            ['bits' => 32, 'partition' => 8, 'strength' => 5],
            ['bits' => 32, 'partition' => 4, 'strength' => 10],
            ['bits' => 64, 'partition' => 8, 'strength' => 5],
            ['bits' => 64, 'partition' => 4, 'strength' => 8],
            ['bits' => 16, 'partition' => 2, 'strength' => 5],
        ];

        $this->forAll(
            Generators::choose(0, count($validConfigs) - 1),
            Generators::choose(0, (1 << 30) - 1),
        )->then(function (int $configIdx, int $number) use ($validConfigs): void {
            $config    = $validConfigs[$configIdx];
            $bits      = $config['bits'];
            $partition = $config['partition'];
            $strength  = $config['strength'];

            $maxVal = (1 << min($bits, 30)) - 1;
            $number = $number % ($maxVal + 1);

            $cipher    = new CaesarCipher($bits, $partition, $strength);
            $encrypted = $cipher->encrypt($number);
            $decrypted = $cipher->decrypt($encrypted);

            $this->assertSame($number, $decrypted,
                "CaesarCipher(bits=$bits,part=$partition,str=$strength) round-trip failed for $number");
        });
    }

    #[Test]
    public function property5b_caesarCipherStringRoundTrip(): void
    {
        $this->forAll(
            Generators::elements([8, 16, 32, 64]),
            Generators::string(),
        )->then(function (int $bits, string $str): void {
            if ($str === '') return;

            $cipher    = new CaesarCipher($bits, 8, 5);
            $encrypted = $cipher->encrypt($str);
            $decrypted = $cipher->decrypt($encrypted);

            $this->assertSame($str, $decrypted,
                "CaesarCipher(bits=$bits) string round-trip failed");
        });
    }

    // ════════════════════════════════════════════════════════════════════
    // Property 6: Rc4 symmetric round-trip
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function property6_rc4SymmetricRoundTrip(): void
    {
        $byteString = Generators::map(
            fn (array $bytes): string => implode('', array_map('chr', $bytes)),
            Generators::vector(
                16,
                Generators::choose(0, 255),
            ),
        );

        $this->forAll($byteString, $byteString)
            ->then(function (string $key, string $input): void {
                $encrypted = Rc4::rc4($key, $input);
                $decrypted = Rc4::rc4($key, $encrypted);

                $this->assertSame($input, $decrypted, "Rc4 round-trip failed");
            });
    }

    // ════════════════════════════════════════════════════════════════════
    // Property 7: DataPacker pack/unpack round-trip
    // ════════════════════════════════════════════════════════════════════

    #[Test]
    public function property7_dataPackerRoundTrip(): void
    {
        $serializableValue = Generators::oneOf(
            Generators::string(),
            Generators::int(),
            Generators::float(),
            Generators::bool(),
            Generators::constant(null),
        );

        $packer = new DataPacker();

        $this->forAll($serializableValue)
            ->then(function (mixed $value) use ($packer): void {
                $packed   = $packer->pack($value);
                $unpacked = $packer->unpack($packed);

                $this->assertEquals($value, $unpacked,
                    "DataPacker round-trip failed for type=" . gettype($value));
            });
    }
}
