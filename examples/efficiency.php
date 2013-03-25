<?php

set_include_path(__DIR__ . '/../src');
spl_autoload_register();

use Riimu\Kit\NumberConversion\BaseConverter;
use Riimu\Kit\NumberConversion\DecimalConverter;


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

$converter = new BaseConverter($source, $target);
$backwards = new BaseConverter($target, $source);

$timer = microtime(true);
for ($i = 0; $i < $count; $i++) {
    $backwards->convertByReplace($converter->convertByReplace($number));
}
echo round(microtime(true) - $timer, 4) . 's - Replace conversion' . PHP_EOL;

$timer = microtime(true);
for ($i = 0; $i < $count; $i++) {
    $backwards->convertByReplaceNum($converter->convertByReplaceNum($number));
}
echo round(microtime(true) - $timer, 4) . 's - Replace conversion (Num)' . PHP_EOL;

$timer = microtime(true);
for ($i = 0; $i < $count; $i++) {
    $backwards->convertByReplaceMath($converter->convertByReplaceMath($number));
}
echo round(microtime(true) - $timer, 4) . 's - Replace conversion (Math)' . PHP_EOL;

$timer = microtime(true);
for ($i = 0; $i < $count; $i++) {
    $backwards->convertByReplaceOrig($converter->convertByReplaceOrig($number));
}
echo round(microtime(true) - $timer, 4) . 's - Replace conversion (Orig)' . PHP_EOL;
die;

/*$converter->setDecimalConverter(new DecimalConverter\InternalConverter());
$timer = microtime(true);
for ($i = 0; $i < $count; $i++) {
    $converter->convertViaDecimal($number);
}
echo round(microtime(true) - $timer, 4) . 's - Internal conversion' . PHP_EOL;*/

if (function_exists('bcadd')) {
    $converter->setDecimalConverter(new DecimalConverter\BCMathConverter());
    $timer = microtime(true);
    for ($i = 0; $i < $count; $i++) {
        $converter->convertViaDecimal($number);
    }
    echo round(microtime(true) - $timer, 4) . 's - BCMath conversion' . PHP_EOL;
}

if (function_exists('gmp_add')) {
    $converter->setDecimalConverter(new DecimalConverter\GMPConverter());
    $timer = microtime(true);
    for ($i = 0; $i < $count; $i++) {
        $converter->convertViaDecimal($number);
    }
    echo round(microtime(true) - $timer, 4) . 's - GMP conversion' . PHP_EOL;
}

$timer = microtime(true);
for ($i = 0; $i < $count; $i++) {
    $converter->convertDirectly($number);
}
echo round(microtime(true) - $timer, 4) . 's - Direct conversion' . PHP_EOL;

$timer = microtime(true);
for ($i = 0; $i < $count; $i++) {
    BaseConverter::customConvert($number, $source, $target);
}
echo round(microtime(true) - $timer, 4) . 's - Custom conversion' . PHP_EOL;
