<?php
declare(strict_types=1);

use Eris\Generators;
use Eris\TestTrait;
use Oasis\Mlib\Utils\StringUtils;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StringUtilsPropertyTest extends TestCase
{
    use TestTrait;

    // ─── Function equivalence (startsWith / endsWith) ───────────────────

    #[Test]
    public function functionEquivalence(): void
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

    // ─── Chopdown post-condition ────────────────────────────────────────

    #[Test]
    public function chopdownPostCondition(): void
    {
        $this->forAll(
            Generators::string(),
            Generators::choose(0, 50),
            Generators::bool(),
        )->then(function (string $input, int $maxLen, bool $byteMode): void {
            $result = StringUtils::stringChopdown($input, $maxLen, $byteMode);

            if ($byteMode) {
                $this->assertLessThanOrEqual($maxLen, strlen($result),
                    "stringChopdown byte mode: output bytes must be <= maxLength");
            } else {
                $this->assertLessThanOrEqual($maxLen, mb_strlen($result, 'UTF-8'),
                    "stringChopdown char mode: output chars must be <= maxLength");
            }
        });
    }
}
