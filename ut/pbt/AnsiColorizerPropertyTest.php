<?php
declare(strict_types=1);

use Eris\Generators;
use Eris\TestTrait;
use Oasis\Mlib\Utils\AnsiColor;
use Oasis\Mlib\Utils\AnsiColorizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AnsiColorizerPropertyTest extends TestCase
{
    use TestTrait;

    #[Test]
    public function outputCorrectness(): void
    {
        $closeTag = "\e[0m";

        $basicColors = [
            AnsiColor::Black, AnsiColor::Red, AnsiColor::Green, AnsiColor::Yellow,
            AnsiColor::Blue, AnsiColor::Magenta, AnsiColor::Cyan, AnsiColor::White,
        ];
        $lightColors = [
            AnsiColor::LightBlack, AnsiColor::LightRed, AnsiColor::LightGreen, AnsiColor::LightYellow,
            AnsiColor::LightBlue, AnsiColor::LightMagenta, AnsiColor::LightCyan, AnsiColor::LightWhite,
        ];

        $this->forAll(Generators::string(), Generators::choose(0, 255))
            ->then(function (string $text, int $intColor) use ($closeTag, $basicColors, $lightColors): void {
                if ($text === '') $text = 'x';

                foreach ($basicColors as $color) {
                    $fg = AnsiColorizer::foreground($text, $color);
                    $this->assertStringContainsString("\e[" . (30 + $color->value) . "m", $fg);
                    $this->assertStringEndsWith($closeTag, $fg);
                    $this->assertStringContainsString($text, $fg);

                    $bg = AnsiColorizer::background($text, $color);
                    $this->assertStringContainsString("\e[" . (40 + $color->value) . "m", $bg);
                    $this->assertStringEndsWith($closeTag, $bg);
                    $this->assertStringContainsString($text, $bg);
                }

                foreach ($lightColors as $color) {
                    $fg = AnsiColorizer::foreground($text, $color);
                    $this->assertStringContainsString("\e[1m", $fg);
                    $this->assertStringEndsWith($closeTag, $fg);

                    $bg = AnsiColorizer::background($text, $color);
                    $this->assertStringContainsString("\e[1m", $bg);
                    $this->assertStringEndsWith($closeTag, $bg);
                }

                $fg256 = AnsiColorizer::foreground($text, $intColor);
                $this->assertStringContainsString("38;5;{$intColor}", $fg256);
                $this->assertStringEndsWith($closeTag, $fg256);

                $bg256 = AnsiColorizer::background($text, $intColor);
                $this->assertStringContainsString("48;5;{$intColor}", $bg256);
                $this->assertStringEndsWith($closeTag, $bg256);
            });
    }
}
