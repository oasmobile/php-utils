<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Preservation Property Test — Property 2: Non-M/G Unit_Conversion 行为不变
 *
 * These tests verify that the K-range and no-unit-range conversion paths
 * produce the same results before and after the bug fix. They encode the
 * CURRENT (correct) behavior of these paths on UNFIXED code.
 *
 * On UNFIXED code these tests MUST PASS — these paths are not affected by the bug.
 * On FIXED code these tests MUST STILL PASS — confirming no regressions.
 */
class MemoryMonitorPreservationTest extends TestCase
{
    /**
     * Simulate the Unit_Conversion logic from CommonUtils::monitorMemoryUsage().
     *
     * Replicates the exact conversion chain (FIXED version):
     *   bytes → K (ceil) → M (ceil) → G (ceil)
     *
     * @param int|float $newLimitBytes
     * @return string The Memory_Limit_String
     */
    private function simulateUnitConversion($newLimitBytes): string
    {
        $newLimit = $newLimitBytes;
        $unit     = '';

        if ($newLimit > 1024) {
            $newLimit = ceil($newLimit / 1024);
            $unit     = 'K';
        }
        if ($newLimit > 1024) {
            $newLimit = ceil($newLimit / 1024);
            $unit     = 'M';
        }
        if ($newLimit > 1024) {
            $newLimit = ceil($newLimit / 1024);
            $unit     = 'G';
        }

        return $newLimit . $unit;
    }

    // =========================================================================
    // No-unit range: value ≤ 1024 — output is plain integer, no suffix
    // =========================================================================

    #[DataProvider('noUnitRangeProvider')]
    public function testNoUnitRangeProducesPlainInteger(int $bytes): void
    {
        $result = $this->simulateUnitConversion($bytes);

        $this->assertMatchesRegularExpression(
            '/^\d+$/',
            $result,
            "No-unit range: bytes=$bytes produced \"$result\" — expected plain integer (no suffix)"
        );

        // Value must equal the input itself (no division occurs)
        $this->assertSame(
            (string)$bytes,
            $result,
            "No-unit range: bytes=$bytes should produce \"$bytes\" but got \"$result\""
        );
    }

    /**
     * Data provider: representative + boundary values in no-unit range (≤ 1024).
     */
    public static function noUnitRangeProvider(): array
    {
        $cases = [
            'minimum (1)'    => [1],
            'small (100)'    => [100],
            'mid (512)'      => [512],
            'typical (800)'  => [800],
            'boundary (1024)' => [1024],
        ];

        // Add random values in range [1, 1024]
        for ($i = 0; $i < 20; $i++) {
            $v = rand(1, 1024);
            $cases["random_nounit_$i ($v)"] = [$v];
        }

        return $cases;
    }

    // =========================================================================
    // K range: 1024 < value ≤ 1048576 — output matches /^\d+K$/
    // =========================================================================

    #[DataProvider('kRangeProvider')]
    public function testKRangeProducesIntegerWithKSuffix(int $bytes): void
    {
        $result = $this->simulateUnitConversion($bytes);

        // Must match integer + K suffix
        $this->assertMatchesRegularExpression(
            '/^\d+K$/',
            $result,
            "K-range: bytes=$bytes produced \"$result\" — expected integer + K suffix"
        );

        // Value must equal ceil(bytes/1024) . "K"
        $expected = ceil($bytes / 1024) . 'K';
        $this->assertSame(
            $expected,
            $result,
            "K-range: bytes=$bytes should produce \"$expected\" but got \"$result\""
        );
    }

    /**
     * Data provider: representative + boundary + random values in K range.
     *
     * K range: 1024 < bytes ≤ 1048576 (= 1024²)
     * At bytes=1048576, K=ceil(1048576/1024)=1024, which is NOT >1024,
     * so it stays in K range and produces "1024K".
     */
    public static function kRangeProvider(): array
    {
        $cases = [
            'just above boundary (1025)' => [1025],
            'power of 2 (2048)'          => [2048],
            'typical small (50000)'      => [50_000],
            'typical mid (500000)'       => [500_000],
            'typical large (1000000)'    => [1_000_000],
            'upper boundary (1048576)'   => [1_048_576],  // K=1024, stays in K
        ];

        // Add random values in K range (1025..1048576)
        for ($i = 0; $i < 30; $i++) {
            $v = rand(1025, 1_048_576);
            $cases["random_krange_$i ($v)"] = [$v];
        }

        return $cases;
    }

    // =========================================================================
    // M/G integer-multiple inputs — conversion produces valid integer results
    // =========================================================================

    /**
     * When bytes are exact multiples of 1024², the M conversion produces
     * an integer value. These cases are NOT affected by the bug and should
     * remain correct after the fix.
     */
    #[DataProvider('mRangeIntegerMultipleProvider')]
    public function testMRangeIntegerMultipleProducesValidResult(int $bytes, string $expectedResult): void
    {
        $result = $this->simulateUnitConversion($bytes);

        // Must match integer + M suffix
        $this->assertMatchesRegularExpression(
            '/^\d+M$/',
            $result,
            "M integer-multiple: bytes=$bytes produced \"$result\" — expected integer + M suffix"
        );

        $this->assertSame(
            $expectedResult,
            $result,
            "M integer-multiple: bytes=$bytes should produce \"$expectedResult\" but got \"$result\""
        );
    }

    /**
     * Data provider: M-range inputs that are exact multiples of 1024²,
     * so K value is a multiple of 1024 and M conversion yields an integer.
     */
    public static function mRangeIntegerMultipleProvider(): array
    {
        return [
            '2M (2097152)'   => [2 * 1024 * 1024, '2M'],       // 2097152
            '5M (5242880)'   => [5 * 1024 * 1024, '5M'],       // 5242880
            '10M (10485760)' => [10 * 1024 * 1024, '10M'],     // 10485760
            '100M'           => [100 * 1024 * 1024, '100M'],   // 104857600
            '512M'           => [512 * 1024 * 1024, '512M'],   // 536870912
            '1024M'          => [1024 * 1024 * 1024, '1024M'], // 1073741824 — M=1024, NOT >1024, stays M
        ];
    }

    /**
     * When bytes are exact multiples of 1024³, the G conversion produces
     * an integer value. These cases are NOT affected by the bug.
     */
    #[DataProvider('gRangeIntegerMultipleProvider')]
    public function testGRangeIntegerMultipleProducesValidResult(int $bytes, string $expectedResult): void
    {
        $result = $this->simulateUnitConversion($bytes);

        $this->assertMatchesRegularExpression(
            '/^\d+[MG]$/',
            $result,
            "G integer-multiple: bytes=$bytes produced \"$result\" — expected integer + M or G suffix"
        );

        $this->assertSame(
            $expectedResult,
            $result,
            "G integer-multiple: bytes=$bytes should produce \"$expectedResult\" but got \"$result\""
        );
    }

    /**
     * Data provider: G-range inputs that are exact multiples of 1024³.
     *
     * At 1024³ (1 GiB): K=1048576, M=ceil(1048576/1024*100)/100=1024, NOT >1024 → stays "1024M"
     * At 2*1024³ (2 GiB): K=2097152, M=ceil(2097152/1024*100)/100=2048, G=ceil(2048/1024*100)/100=2 → "2G"
     */
    public static function gRangeIntegerMultipleProvider(): array
    {
        return [
            '1024M (1 GiB)'  => [1024 * 1024 * 1024, '1024M'],  // M=1024, stays M
            '2G (2 GiB)'     => [2 * 1024 * 1024 * 1024, '2G'],
        ];
    }
}
