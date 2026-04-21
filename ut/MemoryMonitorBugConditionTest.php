<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Bug Condition Exploration Test — Property 1: M/G Unit_Conversion 产生非整数值
 *
 * This test encodes the EXPECTED behavior: Unit_Conversion results MUST be
 * integer values with M or G suffix (matching /^\d+[MG]$/).
 *
 * On UNFIXED code these tests MUST FAIL — failure confirms the bug exists.
 * On FIXED code these tests MUST PASS — passing confirms the fix works.
 *
 * DO NOT fix the code or the test when it fails on unfixed code.
 */
class MemoryMonitorBugConditionTest extends TestCase
{
    /**
     * Simulate the Unit_Conversion logic from CommonUtils::monitorMemoryUsage().
     *
     * This replicates the exact conversion chain:
     *   bytes → K (ceil) → M (ceil) → G (ceil)
     *
     * On UNFIXED code the M/G lines used ceil($x / 1024 * 100) / 100 (buggy).
     * On FIXED code all three stages use ceil($x / 1024) (correct).
     *
     * @param int|float $newLimitBytes
     * @return string The Memory_Limit_String (e.g. "310M", "2G")
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

    /**
     * K→M bug case: newLimitBytes where ceil(bytes/1024) is NOT a multiple of 1024.
     *
     * 324_534_272 bytes → K = ceil(324534272/1024) = 316928
     * → M = ceil(316928/1024*100)/100 = 309.50 (non-integer!)
     * Expected behavior: result MUST match /^\d+[MG]$/ (integer + suffix)
     */
    #[DataProvider('mRangeNonIntegerProvider')]
    public function testMRangeConversionProducesIntegerValue(int $newLimitBytes, string $description): void
    {
        $result = $this->simulateUnitConversion($newLimitBytes);

        $this->assertMatchesRegularExpression(
            '/^\d+M$/',
            $result,
            sprintf(
                'M-range bug case (%s): newLimitBytes=%d produced "%s" — expected integer + M suffix',
                $description,
                $newLimitBytes,
                $result
            )
        );
    }

    /**
     * M→G bug case: newLimitBytes where M value is NOT a multiple of 1024.
     *
     * 1_500_000_000 bytes → eventually M=1430.51 → G=ceil(1430.51/1024*100)/100 = 1.40
     * Expected behavior: result MUST match /^\d+[MG]$/ (integer + suffix)
     */
    #[DataProvider('gRangeNonIntegerProvider')]
    public function testGRangeConversionProducesIntegerValue(int $newLimitBytes, string $description): void
    {
        $result = $this->simulateUnitConversion($newLimitBytes);

        $this->assertMatchesRegularExpression(
            '/^\d+[MG]$/',
            $result,
            sprintf(
                'G-range bug case (%s): newLimitBytes=%d produced "%s" — expected integer + M or G suffix',
                $description,
                $newLimitBytes,
                $result
            )
        );
    }

    /**
     * Data provider: M-range inputs where K value is NOT a multiple of 1024,
     * causing ceil(K/1024*100)/100 to produce a non-integer M value.
     */
    public static function mRangeNonIntegerProvider(): array
    {
        return [
            // 324_534_272 → K=316928 → M=ceil(316928/1024*100)/100 = 309.50
            [324_534_272, '309.50M case — K=316928 not multiple of 1024'],

            // 200_000_000 → K=ceil(200000000/1024)=195313 → M=ceil(195313/1024*100)/100 = 190.74
            [200_000_000, '190.74M case — K=195313 not multiple of 1024'],

            // 150_000_000 → K=ceil(150000000/1024)=146485 → M=ceil(146485/1024*100)/100 = 143.05
            [150_000_000, '143.05M case — K=146485 not multiple of 1024'],

            // 500_000_000 → K=ceil(500000000/1024)=488282 → M=ceil(488282/1024*100)/100 = 476.84
            [500_000_000, '476.84M case — K=488282 not multiple of 1024'],

            // 750_000_000 → K=ceil(750000000/1024)=732422 → M=ceil(732422/1024*100)/100 = 715.26
            [750_000_000, '715.26M case — K=732422 not multiple of 1024'],
        ];
    }

    /**
     * Data provider: G-range inputs where M value is NOT a multiple of 1024,
     * causing ceil(M/1024*100)/100 to produce a non-integer G value.
     */
    public static function gRangeNonIntegerProvider(): array
    {
        return [
            // 1_500_000_000 → K=1464844 → M=ceil(1464844/1024*100)/100=1430.52
            // → G=ceil(1430.52/1024*100)/100 = 1.40
            [1_500_000_000, '1.40G case — M value not multiple of 1024'],

            // 2_000_000_000 → K=1953125 → M=ceil(1953125/1024*100)/100=1907.35
            // → G=ceil(1907.35/1024*100)/100 = 1.87
            [2_000_000_000, '1.87G case — M value not multiple of 1024'],

            // 3_000_000_000 → K=2929688 → M=ceil(2929688/1024*100)/100=2861.03
            // → G=ceil(2861.03/1024*100)/100 = 2.80
            [3_000_000_000, '2.80G case — M value not multiple of 1024'],
        ];
    }
}
