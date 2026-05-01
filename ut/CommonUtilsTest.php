<?php
declare(strict_types=1);

use Oasis\Mlib\Utils\CommonUtils;
use PHPUnit\Framework\TestCase;

class CommonUtilsTest extends TestCase
{
    public function testUnsignedRightShiftZeroBits(): void
    {
        $this->assertEquals(100, CommonUtils::unsignedRightShift(100, 0));
        $this->assertEquals(-1, CommonUtils::unsignedRightShift(-1, 0));
    }

    public function testUnsignedRightShiftPositiveNumber(): void
    {
        // 256 >> 4 = 16
        $this->assertEquals(16, CommonUtils::unsignedRightShift(256, 4));
        // 1024 >> 2 = 256
        $this->assertEquals(256, CommonUtils::unsignedRightShift(1024, 2));
    }

    public function testUnsignedRightShiftNegativeNumber(): void
    {
        // For negative numbers, unsigned right shift should not sign-extend
        $result = CommonUtils::unsignedRightShift(-1, 1);
        $this->assertGreaterThan(0, $result);
    }

    public function testUnsignedRightShiftLargeBits(): void
    {
        $result = CommonUtils::unsignedRightShift(PHP_INT_MAX, 32);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testIsRunningFromCommandLine(): void
    {
        // In PHPUnit context, we are always running from CLI
        $this->assertTrue(CommonUtils::isRunningFromCommandLine());
    }

    public function testMonitorMemoryUsageDoesNotThrow(): void
    {
        // Just ensure it runs without error; actual memory adjustment depends on runtime state
        CommonUtils::monitorMemoryUsage();
        $this->assertTrue(true);
    }

    public function testMonitorMemoryUsageWithCustomParams(): void
    {
        // Use very low minUsage and thresholds to trigger adjustment logic
        CommonUtils::monitorMemoryUsage(1024, 1, 2);
        $this->assertTrue(true);
    }

    public function testRegisterMemoryMonitorForTick(): void
    {
        CommonUtils::registerMemoryMonitorForTick();
        // Just ensure it doesn't throw
        $this->assertTrue(true);
    }
}
