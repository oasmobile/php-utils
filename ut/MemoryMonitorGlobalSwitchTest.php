<?php

use Oasis\Mlib\Utils\CommonUtils;
use PHPUnit\Framework\TestCase;

/**
 * Global_Switch Property Test — Property 3: enable/disable memory monitor
 *
 * Tests verify:
 * - disableMemoryMonitor() prevents monitorMemoryUsage() from calling ini_set
 * - enableMemoryMonitor() restores normal execution
 * - Default state (enabled) executes monitoring logic normally
 * - enable/disable does NOT reset internal static state ($isLowest, $neverReset)
 */
class MemoryMonitorGlobalSwitchTest extends TestCase
{
    private string $savedMemoryLimit;

    protected function setUp(): void
    {
        $this->savedMemoryLimit = ini_get('memory_limit');
        // Ensure monitor is enabled before each test
        CommonUtils::enableMemoryMonitor();
    }

    protected function tearDown(): void
    {
        // Restore original memory_limit and re-enable monitor
        ini_set('memory_limit', $this->savedMemoryLimit);
        CommonUtils::enableMemoryMonitor();
    }

    /**
     * Test: disableMemoryMonitor() prevents monitorMemoryUsage() from changing memory_limit.
     *
     * Strategy: Set a known memory_limit, disable the monitor, call monitorMemoryUsage()
     * with thresholds that would normally trigger an adjustment, verify memory_limit unchanged.
     */
    public function testDisabledMonitorDoesNotChangeMemoryLimit(): void
    {
        // Set a known limit that is relatively low to guarantee expansion would trigger
        ini_set('memory_limit', '16M');
        $limitBefore = ini_get('memory_limit');

        CommonUtils::disableMemoryMonitor();

        // upperThreshold=1 means usage% > 1% triggers expansion (almost always true)
        CommonUtils::monitorMemoryUsage(128000000, 1, 1);

        $limitAfter = ini_get('memory_limit');

        $this->assertSame(
            $limitBefore,
            $limitAfter,
            "memory_limit should not change when monitor is disabled"
        );
    }

    /**
     * Test: enableMemoryMonitor() restores normal execution after disable.
     *
     * Strategy: Disable, verify no change, re-enable, verify function executes.
     */
    public function testEnableRestoresNormalExecution(): void
    {
        // Set a low limit to guarantee expansion triggers
        ini_set('memory_limit', '16M');

        // Disable
        CommonUtils::disableMemoryMonitor();

        $limitBeforeDisabled = ini_get('memory_limit');
        CommonUtils::monitorMemoryUsage(128000000, 1, 1);
        $this->assertSame(
            $limitBeforeDisabled,
            ini_get('memory_limit'),
            "memory_limit should not change while disabled"
        );

        // Re-enable
        CommonUtils::enableMemoryMonitor();

        // Set low limit again to ensure expansion triggers
        ini_set('memory_limit', '16M');
        $limitBeforeEnabled = ini_get('memory_limit');

        // upperThreshold=1 forces expansion for any non-trivial memory usage
        CommonUtils::monitorMemoryUsage(128000000, 1, 1);

        $limitAfterEnabled = ini_get('memory_limit');

        // After re-enabling, the monitor should have adjusted memory_limit
        $this->assertNotSame(
            $limitBeforeEnabled,
            $limitAfterEnabled,
            "After re-enabling, monitorMemoryUsage() should adjust memory_limit. "
            . "Before: $limitBeforeEnabled, After: $limitAfterEnabled"
        );
    }

    /**
     * Test: Default state (no disable called) executes monitoring logic normally.
     */
    public function testDefaultStateExecutesMonitoring(): void
    {
        // setUp() already called enableMemoryMonitor()
        // Set a low limit to guarantee expansion triggers
        ini_set('memory_limit', '16M');
        $limitBefore = ini_get('memory_limit');

        // upperThreshold=1 forces expansion
        CommonUtils::monitorMemoryUsage(128000000, 1, 1);

        $limitAfter = ini_get('memory_limit');

        $this->assertNotSame(
            $limitBefore,
            $limitAfter,
            "Default state should allow monitorMemoryUsage() to execute and adjust limit. "
            . "Before: $limitBefore, After: $limitAfter"
        );
    }

    /**
     * Test: Disabling and re-enabling does NOT reset $isLowest / $neverReset.
     *
     * Strategy:
     * 1. Force an expand adjustment (sets $neverReset=false, $isLowest=false)
     * 2. Disable then re-enable
     * 3. Set a very high memory_limit and trigger the shrink path
     *    (requires $neverReset=false AND $isLowest=false)
     * 4. If internal state was preserved, shrink triggers and limit changes
     *    If state was reset ($neverReset=true), shrink is blocked
     *
     * Note: static variables persist across tests in the same process, so
     * previous tests may have already set $neverReset=false. We explicitly
     * trigger an expand to guarantee $isLowest=false (expand sets it to false).
     */
    public function testEnableDisableDoesNotResetInternalState(): void
    {
        // Step 1: Force an EXPAND adjustment to guarantee:
        //   $isLowest = false (expand path always sets this)
        //   $neverReset = false (any adjustment sets this)
        ini_set('memory_limit', '16M');
        // upperThreshold=1 → usage% > 1% is true → expand triggers
        CommonUtils::monitorMemoryUsage(128000000, 1, 1);

        // Step 2: Disable and re-enable
        CommonUtils::disableMemoryMonitor();
        CommonUtils::enableMemoryMonitor();

        // Step 3: Set a very high memory_limit so usage% is very low.
        // Use a minUsage high enough that the shrink result won't be below
        // actual memory usage (PHP refuses to lower memory_limit below current usage).
        // 128M is safely above typical test process usage (~8-10M).
        ini_set('memory_limit', '4G');

        // lowerThreshold=99 → usage% < 99% is true → shrink path entered
        // upperThreshold=100 → usage% > 100% is never true → no expand
        // minUsage=128M → shrink result will be at least 128M (above actual usage)
        // Shrink requires: $neverReset=false AND $isLowest=false
        CommonUtils::monitorMemoryUsage(128 * 1024 * 1024, 99, 100);

        $afterShrink = ini_get('memory_limit');

        // If internal state was preserved, the shrink path executed and
        // changed the limit from 4G to something smaller (128M).
        $this->assertNotSame(
            '4G',
            $afterShrink,
            "After disable/enable cycle, internal state should be preserved — "
            . "shrink path should be reachable (\$neverReset should still be false, "
            . "\$isLowest should still be false). Got: $afterShrink"
        );
    }
}
