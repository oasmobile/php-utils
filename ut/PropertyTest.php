<?php
declare(strict_types=1);
/**
 * Property-Based Tests for release-3.0.0
 *
 * Manual random input generation + loop assertions (no external PBT library).
 * Each property test runs 100+ iterations.
 *
 * Feature: release-3.0.0
 */

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
use Oasis\Mlib\Utils\Validators\Array2DValidator;
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
    // ─── Helpers: random value generators ───────────────────────────────

    private function randomString(int $maxLen = 50): string
    {
        $len = mt_rand(0, $maxLen);
        $s   = '';
        for ($i = 0; $i < $len; $i++) {
            $s .= chr(mt_rand(32, 126));
        }
        return $s;
    }

    private function randomBinaryString(int $maxLen = 30): string
    {
        $len = mt_rand(1, $maxLen);
        $s   = '';
        for ($i = 0; $i < $len; $i++) {
            $s .= chr(mt_rand(0, 255));
        }
        return $s;
    }

    private function randomScalar(): mixed
    {
        $type = mt_rand(0, 6);
        return match ($type) {
            0 => $this->randomString(30),
            1 => mt_rand(-1000, 1000),
            2 => mt_rand(-1000, 1000) / (mt_rand(1, 100)),
            3 => (bool)mt_rand(0, 1),
            4 => null,
            5 => [mt_rand(0, 10), $this->randomString(5)],
            6 => $this->randomString(15),
        };
    }

    private function randomSerializableValue(): mixed
    {
        $type = mt_rand(0, 5);
        return match ($type) {
            0 => $this->randomString(30),
            1 => mt_rand(-10000, 10000),
            2 => mt_rand(-1000, 1000) / max(1, mt_rand(1, 100)),
            3 => (bool)mt_rand(0, 1),
            4 => null,
            5 => $this->randomArray(2),
        };
    }

    private function randomArray(int $depth = 2): array
    {
        $size = mt_rand(0, 5);
        $arr  = [];
        for ($i = 0; $i < $size; $i++) {
            $key = $this->randomString(8);
            if ($key === '') $key = "k$i";
            if ($depth > 0 && mt_rand(0, 2) === 0) {
                $arr[$key] = $this->randomArray($depth - 1);
            } else {
                $arr[$key] = match (mt_rand(0, 3)) {
                    0 => mt_rand(-100, 100),
                    1 => $this->randomString(10),
                    2 => (bool)mt_rand(0, 1),
                    3 => mt_rand(-100, 100) / max(1, mt_rand(1, 50)),
                };
            }
        }
        return $arr;
    }

    // ════════════════════════════════════════════════════════════════════
    // Property 1: Validator behavior preservation
    // Feature: release-3.0.0, Property 1: Validator behavior preservation
    // Validates: Requirements 3.4, 9.2
    // ════════════════════════════════════════════════════════════════════

    /**
     * Feature: release-3.0.0, Property 1: Validator behavior preservation
     * Validates: Requirements 3.4, 9.2
     */
    #[Test]
    public function property1_validatorBehaviorPreservation(): void
    {
        $iterations = 120;

        for ($i = 0; $i < $iterations; $i++) {
            $input = $this->randomScalar();

            // --- StringValidator ---
            foreach ([true, false] as $strict) {
                foreach ([true, false] as $allowEmpty) {
                    $v = new StringValidator($strict, $allowEmpty);
                    $this->assertValidatorDeterministic($v, $input, "StringValidator(strict=$strict,allowEmpty=$allowEmpty)");
                }
            }

            // --- TrimmedStringValidator with TrimDirection enum ---
            foreach ([true, false] as $strict) {
                foreach (TrimDirection::cases() as $dir) {
                    $v = new TrimmedStringValidator($strict, $dir);
                    $result = $this->safeValidate($v, $input);

                    // If validation succeeded and input was convertible to string, verify trim correctness
                    if ($result['ok'] && is_string($result['value'])) {
                        $stringInput = $result['value']; // already trimmed
                        // Re-validate to confirm determinism
                        $result2 = $this->safeValidate($v, $input);
                        $this->assertSame($result['ok'], $result2['ok'], "TrimmedStringValidator determinism");
                        if ($result2['ok']) {
                            $this->assertSame($result['value'], $result2['value'], "TrimmedStringValidator determinism value");
                        }
                    }

                    $this->assertValidatorDeterministic($v, $input, "TrimmedStringValidator(strict=$strict,dir={$dir->name})");
                }
            }

            // Verify TrimDirection enum produces correct trim results
            $testStr = "  \t hello world \n ";
            $this->assertSame(\trim($testStr), (new TrimmedStringValidator(false, TrimDirection::Both))->validate($testStr));
            $this->assertSame(\ltrim($testStr), (new TrimmedStringValidator(false, TrimDirection::Left))->validate($testStr));
            $this->assertSame(\rtrim($testStr), (new TrimmedStringValidator(false, TrimDirection::Right))->validate($testStr));

            // --- IntegerValidator ---
            foreach ([true, false] as $strict) {
                $v = new IntegerValidator($strict);
                $this->assertValidatorDeterministic($v, $input, "IntegerValidator(strict=$strict)");
            }

            // --- FloatValidator ---
            foreach ([true, false] as $strict) {
                $v = new FloatValidator($strict);
                $this->assertValidatorDeterministic($v, $input, "FloatValidator(strict=$strict)");
            }

            // --- BooleanValidator ---
            foreach ([true, false] as $strict) {
                $v = new BooleanValidator($strict);
                $this->assertValidatorDeterministic($v, $input, "BooleanValidator(strict=$strict)");
            }

            // --- ArrayValidator ---
            foreach ([true, false] as $allowNull) {
                $v = new ArrayValidator($allowNull);
                $this->assertValidatorDeterministic($v, $input, "ArrayValidator(allowNull=$allowNull)");
            }

            // --- Array2DValidator ---
            $v = new Array2DValidator();
            $this->assertValidatorDeterministic($v, $input, "Array2DValidator");

            // --- ObjectValidator ---
            foreach ([true, false] as $allowNull) {
                $v = new ObjectValidator($allowNull);
                $this->assertValidatorDeterministic($v, $input, "ObjectValidator(allowNull=$allowNull)");
            }

            // --- EnumerationValidator ---
            $enumValues = ['a', 'b', 'c', 1, 2, true];
            foreach ([true, false] as $strictType) {
                foreach ([true, false] as $caseSensitive) {
                    $v = new EnumerationValidator($enumValues, $strictType, $caseSensitive);
                    $this->assertValidatorDeterministic($v, $input, "EnumerationValidator(strictType=$strictType,caseSensitive=$caseSensitive)");
                }
            }

            // --- StringLengthValidator ---
            $v = new StringLengthValidator(100, 0, false);
            $this->assertValidatorDeterministic($v, $input, "StringLengthValidator(max=100)");

            // --- RegexValidator ---
            $v = new RegexValidator('/^.*$/');
            $this->assertValidatorDeterministic($v, $input, "RegexValidator(/^.*$/)");

            // --- EmailValidator ---
            $v = new EmailValidator();
            $this->assertValidatorDeterministic($v, $input, "EmailValidator");

            // --- UrlValidator ---
            $v = new UrlValidator();
            $this->assertValidatorDeterministic($v, $input, "UrlValidator");

            // --- DummyValidator ---
            $v = new DummyValidator();
            $result = $v->validate($input);
            $this->assertSame($input, $result, "DummyValidator should pass through any value");

            // --- ChainedValidator ---
            $v = new ChainedValidator(new DummyValidator(), new DummyValidator());
            $result = $v->validate($input);
            $this->assertSame($input, $result, "ChainedValidator(Dummy,Dummy) should pass through");
        }
    }

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

    private function safeGetOptional(ArrayDataProvider $dp, string $key, DataType $dataType, mixed $default): array
    {
        try {
            $value = $dp->getOptional($key, $dataType, $default);
            return ['ok' => true, 'value' => $value, 'exception' => null];
        } catch (DataValidationException $e) {
            return ['ok' => false, 'value' => null, 'exception' => get_class($e)];
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

    // ════════════════════════════════════════════════════════════════════
    // Property 2: DataProvider behavior preservation
    // Feature: release-3.0.0, Property 2: DataProvider behavior preservation
    // Validates: Requirements 3.5, 9.5
    // ════════════════════════════════════════════════════════════════════

    /**
     * Feature: release-3.0.0, Property 2: DataProvider behavior preservation
     * Validates: Requirements 3.5, 9.5
     */
    #[Test]
    public function property2_dataProviderBehaviorPreservation(): void
    {
        $iterations = 120;

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random nested array data
            $data = $this->randomArray(3);
            // Ensure at least some known keys exist for meaningful testing
            $data['intVal']    = mt_rand(-100, 100);
            $data['floatVal']  = mt_rand(-100, 100) / max(1, mt_rand(1, 50));
            $data['strVal']    = $this->randomString(15);
            $data['boolVal']   = (bool)mt_rand(0, 1);
            $data['arrVal']    = [mt_rand(0, 5), mt_rand(0, 5)];
            $data['objVal']    = (object)['a' => 1];
            $data['nested']    = ['child' => ['deep' => mt_rand(0, 100)]];

            $dp = new ArrayDataProvider($data);

            // Test all DataType cases with has() and get()
            foreach (DataType::cases() as $dataType) {
                // Test has() on known keys
                foreach (['intVal', 'floatVal', 'strVal', 'boolVal', 'arrVal', 'objVal'] as $key) {
                    $hasResult = $dp->has($key, $dataType);
                    $this->assertIsBool($hasResult, "has() should return bool for key=$key, type={$dataType->name}");

                    // Verify has() is deterministic
                    $hasResult2 = $dp->has($key, $dataType);
                    $this->assertSame($hasResult, $hasResult2, "has() determinism for key=$key, type={$dataType->name}");
                }

                // Test get() determinism — getOptional may throw DataValidationException on type mismatch
                foreach (['intVal', 'floatVal', 'strVal', 'boolVal'] as $key) {
                    $r1 = $this->safeGetOptional($dp, $key, $dataType, '__default__');
                    $r2 = $this->safeGetOptional($dp, $key, $dataType, '__default__');
                    $this->assertSame($r1['ok'], $r2['ok'], "getOptional() determinism (ok) for key=$key, type={$dataType->name}");
                    if ($r1['ok']) {
                        $this->assertEquals($r1['value'], $r2['value'], "getOptional() determinism (value) for key=$key, type={$dataType->name}");
                    } else {
                        $this->assertSame($r1['exception'], $r2['exception'], "getOptional() determinism (exception) for key=$key, type={$dataType->name}");
                    }
                }

                // Test has() on missing key
                $missingKey = 'nonexistent_' . mt_rand(0, 9999);
                $this->assertFalse($dp->has($missingKey, $dataType), "has() should return false for missing key");
            }

            // Test hierarchical path resolution
            $dp2 = new ArrayDataProvider($data);
            $deepVal = $dp2->getOptional('nested.child.deep', DataType::Int);
            $this->assertSame($data['nested']['child']['deep'], $deepVal, "Hierarchical path resolution");
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // Property 3: AnsiColorizer output correctness
    // Feature: release-3.0.0, Property 3: AnsiColorizer output correctness
    // Validates: Requirements 3.6
    // ════════════════════════════════════════════════════════════════════

    /**
     * Feature: release-3.0.0, Property 3: AnsiColorizer output correctness
     * Validates: Requirements 3.6
     */
    #[Test]
    public function property3_ansiColorizerOutputCorrectness(): void
    {
        $iterations = 120;
        $closeTag   = "\e[0m";

        $basicColors = [
            AnsiColor::Black, AnsiColor::Red, AnsiColor::Green, AnsiColor::Yellow,
            AnsiColor::Blue, AnsiColor::Magenta, AnsiColor::Cyan, AnsiColor::White,
        ];
        $lightColors = [
            AnsiColor::LightBlack, AnsiColor::LightRed, AnsiColor::LightGreen, AnsiColor::LightYellow,
            AnsiColor::LightBlue, AnsiColor::LightMagenta, AnsiColor::LightCyan, AnsiColor::LightWhite,
        ];

        for ($i = 0; $i < $iterations; $i++) {
            $text = $this->randomString(20);
            if ($text === '') $text = 'x'; // ensure non-empty

            // --- Basic colors: foreground ---
            foreach ($basicColors as $color) {
                $result = AnsiColorizer::foreground($text, $color);
                $expectedCode = "\e[" . (30 + $color->value) . "m";
                $this->assertStringContainsString($expectedCode, $result,
                    "foreground basic color {$color->name} should contain escape code");
                $this->assertStringEndsWith($closeTag, $result,
                    "foreground basic color {$color->name} should end with close tag");
                $this->assertStringContainsString($text, $result,
                    "foreground should contain original text");
            }

            // --- Basic colors: background ---
            foreach ($basicColors as $color) {
                $result = AnsiColorizer::background($text, $color);
                $expectedCode = "\e[" . (40 + $color->value) . "m";
                $this->assertStringContainsString($expectedCode, $result,
                    "background basic color {$color->name} should contain escape code");
                $this->assertStringEndsWith($closeTag, $result,
                    "background basic color {$color->name} should end with close tag");
                $this->assertStringContainsString($text, $result,
                    "background should contain original text");
            }

            // --- Light colors: foreground (bold-wrapped) ---
            foreach ($lightColors as $color) {
                $result = AnsiColorizer::foreground($text, $color);
                // Light colors should be bold-wrapped: \e[1m ... \e[0m
                $this->assertStringContainsString("\e[1m", $result,
                    "foreground light color {$color->name} should contain bold code");
                $this->assertStringEndsWith($closeTag, $result,
                    "foreground light color {$color->name} should end with close tag");
                $this->assertStringContainsString($text, $result,
                    "foreground light should contain original text");
            }

            // --- Light colors: background (bold-wrapped) ---
            foreach ($lightColors as $color) {
                $result = AnsiColorizer::background($text, $color);
                $this->assertStringContainsString("\e[1m", $result,
                    "background light color {$color->name} should contain bold code");
                $this->assertStringEndsWith($closeTag, $result,
                    "background light color {$color->name} should end with close tag");
                $this->assertStringContainsString($text, $result,
                    "background light should contain original text");
            }

            // --- Int colors (256-color mode) ---
            $intColor = mt_rand(0, 255);
            $fgResult = AnsiColorizer::foreground($text, $intColor);
            $this->assertStringContainsString("38;5;{$intColor}", $fgResult,
                "foreground int color $intColor should contain 38;5;N");
            $this->assertStringEndsWith($closeTag, $fgResult,
                "foreground int color should end with close tag");

            $bgResult = AnsiColorizer::background($text, $intColor);
            $this->assertStringContainsString("48;5;{$intColor}", $bgResult,
                "background int color $intColor should contain 48;5;N");
            $this->assertStringEndsWith($closeTag, $bgResult,
                "background int color should end with close tag");
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // Property 4: StringUtils function equivalence
    // Feature: release-3.0.0, Property 4: StringUtils function equivalence
    // Validates: Requirements 5.1, 5.2
    // ════════════════════════════════════════════════════════════════════

    /**
     * Feature: release-3.0.0, Property 4: StringUtils function equivalence
     * Validates: Requirements 5.1, 5.2
     */
    #[Test]
    public function property4_stringUtilsFunctionEquivalence(): void
    {
        $iterations = 150;

        for ($i = 0; $i < $iterations; $i++) {
            $haystack = $this->randomString(30);
            $needle   = $this->randomString(10);

            // stringStartsWith should match str_starts_with
            $this->assertSame(
                str_starts_with($haystack, $needle),
                StringUtils::stringStartsWith($haystack, $needle),
                "stringStartsWith('$haystack', '$needle') should equal str_starts_with()"
            );

            // stringEndsWith should match str_ends_with
            $this->assertSame(
                str_ends_with($haystack, $needle),
                StringUtils::stringEndsWith($haystack, $needle),
                "stringEndsWith('$haystack', '$needle') should equal str_ends_with()"
            );
        }

        // Edge cases: empty strings
        $this->assertSame(str_starts_with('', ''), StringUtils::stringStartsWith('', ''));
        $this->assertSame(str_ends_with('', ''), StringUtils::stringEndsWith('', ''));
        $this->assertSame(str_starts_with('abc', ''), StringUtils::stringStartsWith('abc', ''));
        $this->assertSame(str_ends_with('abc', ''), StringUtils::stringEndsWith('abc', ''));
        $this->assertSame(str_starts_with('', 'abc'), StringUtils::stringStartsWith('', 'abc'));
        $this->assertSame(str_ends_with('', 'abc'), StringUtils::stringEndsWith('', 'abc'));
    }

    // ════════════════════════════════════════════════════════════════════
    // Property 5: CaesarCipher encrypt/decrypt round-trip
    // Feature: release-3.0.0, Property 5: CaesarCipher encrypt/decrypt round-trip
    // Validates: Requirements 9.3
    // ════════════════════════════════════════════════════════════════════

    /**
     * Feature: release-3.0.0, Property 5: CaesarCipher encrypt/decrypt round-trip
     * Validates: Requirements 9.3
     */
    #[Test]
    public function property5_caesarCipherRoundTrip(): void
    {
        $iterations = 120;

        // Valid configurations: even bits in (0,64], even partition dividing bits, strength >= 1
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

        for ($i = 0; $i < $iterations; $i++) {
            $config = $validConfigs[mt_rand(0, count($validConfigs) - 1)];
            $bits      = $config['bits'];
            $partition = $config['partition'];
            $strength  = $config['strength'];

            $cipher = new CaesarCipher($bits, $partition, $strength);

            // Integer round-trip: random integer within bit range
            $maxVal = (1 << min($bits, 30)) - 1; // avoid overflow on 32-bit
            $number = mt_rand(0, $maxVal);

            $encrypted = $cipher->encrypt($number);
            $decrypted = $cipher->decrypt($encrypted);
            $this->assertSame($number, $decrypted,
                "CaesarCipher(bits=$bits,part=$partition,str=$strength) int round-trip: encrypt($number) -> decrypt -> $decrypted");

            // String round-trip (only when bits % 8 == 0)
            if ($bits % 8 === 0) {
                $str = $this->randomBinaryString(mt_rand(1, 20));
                $encStr = $cipher->encrypt($str);
                $decStr = $cipher->decrypt($encStr);
                $this->assertSame($str, $decStr,
                    "CaesarCipher(bits=$bits,part=$partition,str=$strength) string round-trip failed");
            }
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // Property 6: Rc4 symmetric round-trip
    // Feature: release-3.0.0, Property 6: Rc4 symmetric round-trip
    // Validates: Requirements 9.3
    // ════════════════════════════════════════════════════════════════════

    /**
     * Feature: release-3.0.0, Property 6: Rc4 symmetric round-trip
     * Validates: Requirements 9.3
     */
    #[Test]
    public function property6_rc4SymmetricRoundTrip(): void
    {
        $iterations = 150;

        for ($i = 0; $i < $iterations; $i++) {
            $key   = $this->randomBinaryString(mt_rand(1, 32));
            $input = $this->randomBinaryString(mt_rand(1, 50));

            $encrypted = Rc4::rc4($key, $input);
            $decrypted = Rc4::rc4($key, $encrypted);

            $this->assertSame($input, $decrypted,
                "Rc4 round-trip: rc4(key, rc4(key, input)) should equal input (iteration $i)");
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // Property 7: DataPacker pack/unpack round-trip
    // Feature: release-3.0.0, Property 7: DataPacker pack/unpack round-trip
    // Validates: Requirements 9.4
    // ════════════════════════════════════════════════════════════════════

    /**
     * Feature: release-3.0.0, Property 7: DataPacker pack/unpack round-trip
     * Validates: Requirements 9.4
     */
    #[Test]
    public function property7_dataPackerRoundTrip(): void
    {
        $iterations = 150;
        $packer     = new DataPacker();

        for ($i = 0; $i < $iterations; $i++) {
            $value = $this->randomSerializableValue();

            $packed   = $packer->pack($value);
            $unpacked = $packer->unpack($packed);

            $this->assertEquals($value, $unpacked,
                "DataPacker round-trip: unpack(pack(value)) should equal value (iteration $i, type=" . gettype($value) . ")");
        }
    }
}
