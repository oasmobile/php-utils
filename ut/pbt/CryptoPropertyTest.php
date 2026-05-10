<?php
declare(strict_types=1);

use Eris\Generators;
use Eris\TestTrait;
use Oasis\Mlib\Utils\CaesarCipher;
use Oasis\Mlib\Utils\Rc4;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CryptoPropertyTest extends TestCase
{
    use TestTrait;

    // ─── CaesarCipher round-trip ────────────────────────────────────────

    #[Test]
    public function caesarCipherRoundTrip(): void
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

    // ─── CaesarCipher string round-trip ─────────────────────────────────

    #[Test]
    public function caesarCipherStringRoundTrip(): void
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

    // ─── CaesarCipher bijectivity ───────────────────────────────────────

    #[Test]
    public function caesarCipherBijectivity(): void
    {
        $this->forAll(
            Generators::choose(0, 65535),
            Generators::choose(0, 65535),
        )->then(function (int $a, int $b): void {
            if ($a === $b) return;

            $cipher = new CaesarCipher(16, 4, 5);
            $encA = $cipher->encrypt($a);
            $encB = $cipher->encrypt($b);

            $this->assertNotSame($encA, $encB,
                "CaesarCipher must be injective: encrypt($a) != encrypt($b)");
        });
    }

    // ─── Rc4 symmetric round-trip ───────────────────────────────────────

    #[Test]
    public function rc4SymmetricRoundTrip(): void
    {
        $byteString = Generators::map(
            fn (array $bytes): string => implode('', array_map('chr', $bytes)),
            Generators::vector(16, Generators::choose(0, 255)),
        );

        $this->forAll($byteString, $byteString)
            ->then(function (string $key, string $input): void {
                $encrypted = Rc4::rc4($key, $input);
                $decrypted = Rc4::rc4($key, $encrypted);

                $this->assertSame($input, $decrypted, "Rc4 round-trip failed");
            });
    }

    // ─── Rc4 key sensitivity ────────────────────────────────────────────

    #[Test]
    public function rc4KeySensitivity(): void
    {
        $byteString = Generators::map(
            fn (array $bytes): string => implode('', array_map('chr', $bytes)),
            Generators::vector(8, Generators::choose(0, 255)),
        );

        $this->forAll($byteString, $byteString, $byteString)
            ->then(function (string $key1, string $key2, string $input): void {
                if ($key1 === $key2 || $input === '') return;

                $enc1 = Rc4::rc4($key1, $input);
                $enc2 = Rc4::rc4($key2, $input);

                $this->assertNotSame($enc1, $enc2,
                    "Rc4 with different keys must produce different ciphertexts");
            });
    }
}
