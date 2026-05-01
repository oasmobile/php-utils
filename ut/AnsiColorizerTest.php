<?php
declare(strict_types=1);

use Oasis\Mlib\Utils\AnsiColor;
use Oasis\Mlib\Utils\AnsiColorizer;
use PHPUnit\Framework\TestCase;

class AnsiColorizerTest extends TestCase
{
    public function testBold(): void
    {
        $result = AnsiColorizer::bold("hello");
        $this->assertStringContainsString("\e[1m", $result);
        $this->assertStringContainsString("hello", $result);
        $this->assertStringEndsWith("\e[0m", $result);
    }

    public function testUnderline(): void
    {
        $result = AnsiColorizer::underline("test");
        $this->assertStringContainsString("\e[4m", $result);
        $this->assertStringContainsString("test", $result);
        $this->assertStringEndsWith("\e[0m", $result);
    }

    public function testReverse(): void
    {
        $result = AnsiColorizer::reverse("rev");
        $this->assertStringContainsString("\e[7m", $result);
        $this->assertStringContainsString("rev", $result);
        $this->assertStringEndsWith("\e[0m", $result);
    }

    public function testForegroundWithBaseColor(): void
    {
        $result = AnsiColorizer::foreground("text", AnsiColor::Red);
        $this->assertStringContainsString("text", $result);
        $this->assertStringEndsWith("\e[0m", $result);
    }

    public function testForegroundWithLightColor(): void
    {
        // Light colors use bold + base color
        $result = AnsiColorizer::foreground("text", AnsiColor::LightRed);
        $this->assertStringContainsString("\e[1m", $result);
        $this->assertStringContainsString("text", $result);
    }

    public function testForegroundWith256Color(): void
    {
        $result = AnsiColorizer::foreground("text", 200);
        $this->assertStringContainsString("38;5;200", $result);
        $this->assertStringContainsString("text", $result);
    }

    public function testBackgroundWithBaseColor(): void
    {
        $result = AnsiColorizer::background("text", AnsiColor::Blue);
        $this->assertStringContainsString("text", $result);
        $this->assertStringEndsWith("\e[0m", $result);
    }

    public function testBackgroundWithLightColor(): void
    {
        $result = AnsiColorizer::background("text", AnsiColor::LightGreen);
        $this->assertStringContainsString("\e[1m", $result);
        $this->assertStringContainsString("text", $result);
    }

    public function testBackgroundWith256Color(): void
    {
        $result = AnsiColorizer::background("text", 100);
        $this->assertStringContainsString("48;5;100", $result);
        $this->assertStringContainsString("text", $result);
    }

    public function testAllLightColors(): void
    {
        $lightColors = [
            AnsiColor::LightBlack,
            AnsiColor::LightRed,
            AnsiColor::LightGreen,
            AnsiColor::LightYellow,
            AnsiColor::LightBlue,
            AnsiColor::LightMagenta,
            AnsiColor::LightCyan,
            AnsiColor::LightWhite,
        ];

        foreach ($lightColors as $color) {
            $result = AnsiColorizer::foreground("x", $color);
            $this->assertStringContainsString("\e[1m", $result);
        }
    }
}
