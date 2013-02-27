<?php

require '../library/Rose/NumberConversion/DecimalConverter/DecimalConverter.php';
require '../library/Rose/NumberConversion/DecimalConverter/BCMathConverter.php';
require '../library/Rose/NumberConversion/DecimalConverter/GMPConverter.php';
require '../library/Rose/NumberConversion/BaseConverter.php';
require '../library/Rose/NumberConversion/NumberBase.php';

echo "Test for efficiency of different algorithms available:\n";

$count = 5;

$number = str_split(
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053' .
    '51234523543204324304531204345024035045302043503203503503200420530350200405350053'
);
$source = str_split('012345');
$target = str_split('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');

$converter = new Rose\NumberConversion\BaseConverter(
    new Rose\NumberConversion\NumberBase($source),
    new Rose\NumberConversion\NumberBase($target));

$timer = microtime(true);
for ($i = 0; $i < $count; $i++) {
    $converter->convertByReplace($number);
}
echo round(microtime(true) - $timer, 4) . 's - Replace conversion' . PHP_EOL;

$timer = microtime(true);
for ($i = 0; $i < $count; $i++) {
    $converter->convertViaDecimal($number);
}
echo round(microtime(true) - $timer, 4) . 's - Decimal conversion' . PHP_EOL;

$timer = microtime(true);
for ($i = 0; $i < $count; $i++) {
    $converter->convertDirectly($number);
}
echo round(microtime(true) - $timer, 4) . 's - Direct conversion' . PHP_EOL;

$timer = microtime(true);
for ($i = 0; $i < $count; $i++) {
    \Rose\NumberConversion\BaseConverter::customConvert($number, $source, $target);
}
echo round(microtime(true) - $timer, 4) . 's - Custom conversion' . PHP_EOL;
