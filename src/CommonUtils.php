<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-01-19
 * Time: 21:00
 */

namespace Oasis\Mlib\Utils;

class CommonUtils
{
    public static function isRunningFromCommandLine(): bool
    {
        static $isCli = null;
        if ($isCli === null) {
            $isCli = (
                !isset($_SERVER['SERVER_SOFTWARE'])
                && (
                    php_sapi_name() == 'cli'
                    || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0)
                )
            );
        }
        
        return $isCli;
    }
    
    public static function monitorMemoryUsage(int $minUsage = 128000000,
                                              int $lowerThreshold = 10,
                                              int $upperThreshold = 70
    ): void
    {
        static $isLowest = false;
        static $neverReset = true;
        
        $currentUsage = memory_get_usage();
        $currentLimit = ini_get('memory_limit');
        $last         = strtolower($currentLimit[strlen($currentLimit) - 1]);
        $currentLimit = match ($last) {
            'g'     => (int)substr($currentLimit, 0, -1) * 1024 * 1024 * 1024,
            'm'     => (int)substr($currentLimit, 0, -1) * 1024 * 1024,
            'k'     => (int)substr($currentLimit, 0, -1) * 1024,
            default => (int)$currentLimit,
        };
        $newLimit        = $currentLimit;
        $usagePercentage = $currentUsage / $currentLimit * 100;
        $resetNeeded     = false;
        if ($usagePercentage > $upperThreshold) {
            $newLimit    = $currentUsage * 100 / (($upperThreshold + $lowerThreshold) / 2);
            $isLowest    = false;
            $resetNeeded = true;
        }
        else if ($usagePercentage < $lowerThreshold && !$neverReset && !$isLowest) {
            $newLimit = $currentUsage * 100 / (($upperThreshold + $lowerThreshold) / 2);
            if ($newLimit < $minUsage) {
                $newLimit = $minUsage;
                $isLowest = true;
            }
            $resetNeeded = true;
        }
        
        if ($resetNeeded) {
            $unit = "";
            if ($newLimit > 1024) {
                $newLimit = ceil($newLimit / 1024);
                $unit     = 'K';
            }
            if ($newLimit > 1024) {
                $newLimit = ceil($newLimit / 1024 * 100) / 100;
                $unit     = 'M';
            }
            if ($newLimit > 1024) {
                $newLimit = ceil($newLimit / 1024 * 100) / 100;
                $unit     = 'G';
            }
            $newLimit = $newLimit . $unit;
            ini_set('memory_limit', $newLimit);
            if (self::isRunningFromCommandLine()) {
                fprintf(
                    STDERR,
                    "memory limit adjusted dynamically - $newLimit (from $currentLimit), cur = $currentUsage\n"
                );
            }
            $neverReset = false;
        }
    }
    
    public static function registerMemoryMonitorForTick(): void
    {
        $function_name = __CLASS__ . "::monitorMemoryUsage";
        register_tick_function($function_name);
    }
    
    /**
     * Makes an unsigned shift of an integer given bits.
     */
    public static function unsignedRightShift(int $num, int $bits): int
    {
        if ($bits == 0) {
            return $num;
        }
        
        return ($num >> $bits) & ~(1 << (8 * PHP_INT_SIZE - 1) >> ($bits - 1));
    }
}
