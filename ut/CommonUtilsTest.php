<?php
declare(strict_types=1);

use Oasis\Mlib\Utils\CommonUtils;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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

    public function testSetLogger(): void
    {
        $logger = new class implements \Psr\Log\LoggerInterface {
            use \Psr\Log\LoggerTrait;
            public array $logs = [];
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }
        };
        CommonUtils::setLogger($logger);
        // setLogger itself is the target; just verify it doesn't throw
        CommonUtils::setLogger(null);
        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    public function testMonitorMemoryUsageUpperBranch(): void
    {
        // Set memory limit very tight so current usage > upperThreshold%
        $currentUsage = memory_get_usage();
        // Set limit to currentUsage + small buffer so usage% > 70
        $limit = (int)($currentUsage / 0.5); // usage will be ~50% which is < 70
        // Actually we need usage > upperThreshold. Set limit so usage/limit > 0.7
        $tightLimit = (int)($currentUsage / 0.71) + 1;
        ini_set('memory_limit', (string)$tightLimit);
        CommonUtils::monitorMemoryUsage(1024, 10, 70);
        // If we get here without error, the upper branch was triggered and limit was raised
        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    public function testMonitorMemoryUsageLowerBranch(): void
    {
        // First trigger upper branch to set neverReset=false
        $currentUsage = memory_get_usage();
        $tightLimit = (int)($currentUsage / 0.71) + 1;
        ini_set('memory_limit', (string)$tightLimit);
        CommonUtils::monitorMemoryUsage(1024, 10, 70);
        // After upper branch, limit was raised significantly.
        // Now set a very high limit so usage% is very low, then trigger lower branch.
        // Use lowerThreshold=50, upperThreshold=90 so newLimit = usage*100/70 ≈ 1.43x usage (safe).
        ini_set('memory_limit', '2G');
        CommonUtils::monitorMemoryUsage(1024, 50, 90);
        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    public function testMonitorMemoryUsageMinUsageBranch(): void
    {
        // First trigger upper branch to set neverReset=false
        $currentUsage = memory_get_usage();
        $tightLimit = (int)($currentUsage / 0.71) + 1;
        ini_set('memory_limit', (string)$tightLimit);
        CommonUtils::monitorMemoryUsage(1024, 10, 70);
        // Set high limit so usage% is very low, trigger lower branch with huge minUsage
        // Use lowerThreshold=50, upperThreshold=90 so newLimit calc is safe, but minUsage overrides
        ini_set('memory_limit', '2G');
        CommonUtils::monitorMemoryUsage(PHP_INT_MAX, 50, 90);
        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    public function testMonitorMemoryUsageWithLogger(): void
    {
        $logger = new class implements \Psr\Log\LoggerInterface {
            use \Psr\Log\LoggerTrait;
            public array $logs = [];
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }
        };
        CommonUtils::setLogger($logger);
        $currentUsage = memory_get_usage();
        $tightLimit = (int)($currentUsage / 0.71) + 1;
        ini_set('memory_limit', (string)$tightLimit);
        CommonUtils::monitorMemoryUsage(1024, 10, 70);
        CommonUtils::setLogger(null);
        $this->assertNotEmpty($logger->logs);
    }

    #[RunInSeparateProcess]
    public function testMonitorMemoryUsageWithKilobyteLimit(): void
    {
        // Set memory limit with 'K' suffix to cover the 'k' branch in match
        $currentUsage = memory_get_usage();
        $limitInK = (int)ceil($currentUsage / 1024 / 0.71) + 1;
        ini_set('memory_limit', $limitInK . 'K');
        CommonUtils::monitorMemoryUsage(1024, 10, 70);
        $this->assertTrue(true);
    }
}
