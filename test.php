#! /usr/local/bin/php
<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-29
 * Time: 21:09
 */

use Oasis\Mlib\Utils\AnsiColorizer;

require_once __DIR__ . "/vendor/autoload.php";

$text = AnsiColorizer::background(AnsiColorizer::foreground('hello', 155), 160);
$text .= AnsiColorizer::bold(' wor');
$text .= AnsiColorizer::reverse(AnsiColorizer::underline(AnsiColorizer::foreground('l', 'light-cyan')));
$text .= AnsiColorizer::foreground('d', 'cyan');

echo $text . PHP_EOL;
