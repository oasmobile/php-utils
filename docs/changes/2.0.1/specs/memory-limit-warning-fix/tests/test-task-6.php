<?php
/**
 * Manual test script for memory-limit-warning-fix spec — Task 6
 *
 * Covers:
 *   6.1 — M 范围非整数倍场景：ini_set 不产生 E_WARNING，memory_limit 为整数+M
 *   6.2 — Global_Switch 禁用/启用场景
 *   6.3 — K 范围行为保留 + CLI stderr 日志输出
 *
 * All scenarios run in isolated subprocesses to control memory_limit precisely.
 *
 * Usage:  php .kiro/specs/memory-limit-warning-fix/tests/test-task-6.php
 * Exit code: 0 = all pass, 1 = at least one failure
 */

$autoloadPath = realpath(__DIR__ . '/../../../../vendor/autoload.php');
if (!$autoloadPath) {
    echo "FATAL: vendor/autoload.php not found\n";
    exit(1);
}

// ── helpers ──────────────────────────────────────────────────────────────

$passed  = 0;
$failed  = 0;

function report(string $id, string $desc, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    $status = $ok ? 'PASS' : 'FAIL';
    $line   = "[$status] $id — $desc";
    if ($detail !== '') {
        $line .= " ($detail)";
    }
    echo $line . PHP_EOL;
    $ok ? $passed++ : $failed++;
}

/**
 * Run a PHP snippet in a subprocess with a specific memory_limit.
 * Returns ['stdout' => string, 'stderr' => string, 'exit' => int].
 */
function runSubprocess(string $code, string $memoryLimit = '256M'): array
{
    $tmpFile = tempnam(sys_get_temp_dir(), 'mmt_') . '.php';
    file_put_contents($tmpFile, $code);

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $cmd  = ['php', '-d', "memory_limit=$memoryLimit", $tmpFile];
    $proc = proc_open($cmd, $descriptors, $pipes);
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($proc);
    unlink($tmpFile);

    return ['stdout' => $stdout, 'stderr' => $stderr, 'exit' => $exit];
}

/** Parse KEY=VALUE lines from stdout into an associative array. */
function parseKV(string $stdout): array
{
    $result = [];
    foreach (explode("\n", trim($stdout)) as $line) {
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $result[trim($key)] = trim($val);
        }
    }
    return $result;
}

// ── 6.1  M 范围非整数倍场景 ─────────────────────────────────────────────
//
// Strategy: start subprocess with memory_limit=10M. After autoload (~2MB),
// allocate ~5.2MB extra to push usage to ~7.2MB (72% of 10M > upperThreshold 70%).
// monitorMemoryUsage() will expand. The new limit in bytes:
//   newLimit = currentUsage * 100 / 40 ≈ usage * 2.5 ≈ 18MB
//   K = ceil(18MB / 1024) = 18355 → M = ceil(18355 / 1024) = 18 → "18M" ✓
// Under OLD formula: M = ceil(18355/1024*100)/100 = 17.93 → "17.93M" → E_WARNING

echo str_repeat('─', 60) . PHP_EOL;
echo "6.1  验证 M 范围非整数倍场景" . PHP_EOL;
echo str_repeat('─', 60) . PHP_EOL;

$script61 = <<<PHP
<?php
require_once '$autoloadPath';
use Oasis\\Mlib\\Utils\\CommonUtils;

// Plant a sentinel error to verify it's not overwritten.
@trigger_error('sentinel_error_for_test', E_USER_NOTICE);

// Allocate memory to push usage above 70% of 10M.
\$junk = str_repeat('x', 5 * 1024 * 1024 + 200000);

// Capture any E_WARNING from ini_set inside monitorMemoryUsage.
\$warningCaptured = null;
set_error_handler(function (\$errno, \$errstr) use (&\$warningCaptured) {
    \$warningCaptured = ['errno' => \$errno, 'errstr' => \$errstr];
    return true;
}, E_WARNING);

CommonUtils::monitorMemoryUsage();

restore_error_handler();

\$newLimit = ini_get('memory_limit');
\$lastErr  = error_get_last();

echo "LIMIT=\$newLimit\n";
echo "VALID=" . (preg_match('/^\d+[MG]\$/', \$newLimit) ? '1' : '0') . "\n";
echo "WARNING=" . (\$warningCaptured === null ? 'none' : \$warningCaptured['errstr']) . "\n";
echo "LAST_ERROR=" . (\$lastErr !== null ? \$lastErr['message'] : 'null') . "\n";
echo "SENTINEL_OK=" . ((\$lastErr !== null && \$lastErr['message'] === 'sentinel_error_for_test') ? '1' : '0') . "\n";
PHP;

$r = runSubprocess($script61, '10M');
$kv = parseKV($r['stdout']);

if (empty($kv)) {
    echo "  [ERROR] Subprocess produced no output. stderr:\n";
    echo "  " . substr($r['stderr'], 0, 300) . PHP_EOL;
    report('6.1-a', "memory_limit 为整数+M/G 后缀", false, 'subprocess failed');
    report('6.1-b', "ini_set 未触发 E_WARNING", false, 'subprocess failed');
    report('6.1-c', "error_get_last() 未被 warning 覆盖", false, 'subprocess failed');
} else {
    report('6.1-a', "memory_limit 为整数+M/G 后缀",
        ($kv['VALID'] ?? '0') === '1',
        "limit={$kv['LIMIT']}");

    report('6.1-b', "ini_set 未触发 E_WARNING",
        ($kv['WARNING'] ?? '') === 'none',
        "warning={$kv['WARNING']}");

    report('6.1-c', "error_get_last() 未被 warning 覆盖 (sentinel preserved)",
        ($kv['SENTINEL_OK'] ?? '0') === '1',
        "last_error={$kv['LAST_ERROR']}");
}

echo PHP_EOL;

// ── 6.2  Global_Switch 禁用/启用场景 ────────────────────────────────────

echo str_repeat('─', 60) . PHP_EOL;
echo "6.2  验证 Global_Switch 禁用/启用场景" . PHP_EOL;
echo str_repeat('─', 60) . PHP_EOL;

$script62 = <<<PHP
<?php
require_once '$autoloadPath';
use Oasis\\Mlib\\Utils\\CommonUtils;

// Allocate memory to push usage above 70% of 10M.
\$junk = str_repeat('x', 5 * 1024 * 1024 + 200000);

// --- Phase 1: disable, then call monitorMemoryUsage ---
CommonUtils::disableMemoryMonitor();
\$limitBefore = ini_get('memory_limit');
CommonUtils::monitorMemoryUsage();
\$limitAfterDisable = ini_get('memory_limit');

echo "BEFORE=\$limitBefore\n";
echo "AFTER_DISABLE=\$limitAfterDisable\n";
echo "UNCHANGED=" . (\$limitBefore === \$limitAfterDisable ? '1' : '0') . "\n";

// --- Phase 2: re-enable, call again ---
CommonUtils::enableMemoryMonitor();
CommonUtils::monitorMemoryUsage();
\$limitAfterEnable = ini_get('memory_limit');

echo "AFTER_ENABLE=\$limitAfterEnable\n";
echo "EXPANDED=" . (\$limitAfterEnable !== \$limitBefore ? '1' : '0') . "\n";
PHP;

$r = runSubprocess($script62, '10M');
$kv = parseKV($r['stdout']);

if (empty($kv)) {
    echo "  [ERROR] Subprocess produced no output. stderr:\n";
    echo "  " . substr($r['stderr'], 0, 300) . PHP_EOL;
    report('6.2-a', "disable 后 memory_limit 未被调整", false, 'subprocess failed');
    report('6.2-b', "enable 后恢复正常扩容", false, 'subprocess failed');
} else {
    report('6.2-a', "disable 后 memory_limit 未被调整",
        ($kv['UNCHANGED'] ?? '0') === '1',
        "before={$kv['BEFORE']}, after_disable={$kv['AFTER_DISABLE']}");

    report('6.2-b', "enable 后恢复正常扩容",
        ($kv['EXPANDED'] ?? '0') === '1',
        "before={$kv['BEFORE']}, after_enable={$kv['AFTER_ENABLE']}");
}

echo PHP_EOL;

// ── 6.3  现有行为保留 + CLI stderr 日志 ──────────────────────────────────

echo str_repeat('─', 60) . PHP_EOL;
echo "6.3  验证现有行为保留" . PHP_EOL;
echo str_repeat('─', 60) . PHP_EOL;

// 6.3-a/b: Expansion produces valid format, no warnings.
// 6.3-c/d: Shrink path also produces valid format, no warnings.
// 6.3-e: CLI stderr log output present.

$script63 = <<<PHP
<?php
require_once '$autoloadPath';
use Oasis\\Mlib\\Utils\\CommonUtils;

// Allocate memory to push usage above 70% of 10M.
\$junk = str_repeat('x', 5 * 1024 * 1024 + 200000);

\$warningCaptured = null;
set_error_handler(function (\$errno, \$errstr) use (&\$warningCaptured) {
    \$warningCaptured = ['errno' => \$errno, 'errstr' => \$errstr];
    return true;
}, E_WARNING);

// --- Expansion ---
CommonUtils::monitorMemoryUsage();
restore_error_handler();

\$expandedLimit = ini_get('memory_limit');
echo "EXPANDED_LIMIT=\$expandedLimit\n";
echo "EXPANDED_VALID=" . (preg_match('/^\d+[KMG]?\$/', \$expandedLimit) ? '1' : '0') . "\n";
echo "EXPAND_WARNING=" . (\$warningCaptured === null ? 'none' : \$warningCaptured['errstr']) . "\n";

// --- Shrink path ---
// Set limit very high so usage% drops below lowerThreshold (10%).
// \$neverReset is now false from the expansion call above.
ini_set('memory_limit', '1G');

\$warningCaptured = null;
set_error_handler(function (\$errno, \$errstr) use (&\$warningCaptured) {
    \$warningCaptured = ['errno' => \$errno, 'errstr' => \$errstr];
    return true;
}, E_WARNING);

CommonUtils::monitorMemoryUsage();
restore_error_handler();

\$shrunkLimit = ini_get('memory_limit');
echo "SHRUNK_LIMIT=\$shrunkLimit\n";
echo "SHRUNK_VALID=" . (preg_match('/^\d+[KMG]?\$/', \$shrunkLimit) ? '1' : '0') . "\n";
echo "SHRINK_WARNING=" . (\$warningCaptured === null ? 'none' : \$warningCaptured['errstr']) . "\n";
PHP;

$r = runSubprocess($script63, '10M');
$kv = parseKV($r['stdout']);

if (empty($kv)) {
    echo "  [ERROR] Subprocess produced no output. stderr:\n";
    echo "  " . substr($r['stderr'], 0, 300) . PHP_EOL;
    report('6.3-a', "扩容后 memory_limit 格式合法 (整数+单位)", false, 'subprocess failed');
    report('6.3-b', "扩容过程无 E_WARNING", false, 'subprocess failed');
    report('6.3-c', "缩容后 memory_limit 格式合法 (整数+单位)", false, 'subprocess failed');
    report('6.3-d', "缩容过程无 E_WARNING", false, 'subprocess failed');
    report('6.3-e', "CLI 模式 stderr 日志输出正常", false, 'subprocess failed');
} else {
    report('6.3-a', "扩容后 memory_limit 格式合法 (整数+单位)",
        ($kv['EXPANDED_VALID'] ?? '0') === '1',
        "limit={$kv['EXPANDED_LIMIT']}");

    report('6.3-b', "扩容过程无 E_WARNING",
        ($kv['EXPAND_WARNING'] ?? '') === 'none',
        "warning={$kv['EXPAND_WARNING']}");

    report('6.3-c', "缩容后 memory_limit 格式合法 (整数+单位)",
        ($kv['SHRUNK_VALID'] ?? '0') === '1',
        "limit={$kv['SHRUNK_LIMIT']}");

    report('6.3-d', "缩容过程无 E_WARNING",
        ($kv['SHRINK_WARNING'] ?? '') === 'none',
        "warning={$kv['SHRINK_WARNING']}");

    // CLI stderr log output.
    $stderrHasLog = str_contains($r['stderr'], 'memory limit adjusted dynamically');
    report('6.3-e', "CLI 模式 stderr 日志输出正常", $stderrHasLog,
        $stderrHasLog ? 'found log line' : "stderr: " . substr($r['stderr'], 0, 200));
}

echo PHP_EOL;

// ── Summary ──────────────────────────────────────────────────────────────

echo str_repeat('═', 60) . PHP_EOL;
echo "Summary: $passed passed, $failed failed" . PHP_EOL;
echo str_repeat('═', 60) . PHP_EOL;

exit($failed > 0 ? 1 : 0);
