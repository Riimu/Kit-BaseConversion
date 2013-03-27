<?php

if ($argc < 3) {
    echo "Usage efficiency.php <repeats> <length> [source-base] [target-base]" . PHP_EOL;
    exit;
}

set_include_path(__DIR__ . '/../src');
spl_autoload_register();

use \Riimu\Kit\NumberConversion\ConversionMethod\ConversionException;

echo "Test for efficiency of different algorithms available:" . PHP_EOL;

$sbase = isset($argv[3]) ? (ctype_digit($argv[3]) ? intval($argv[3]) : $argv[3]) : 2;
$tbase = isset($argv[4]) ? (ctype_digit($argv[4]) ? intval($argv[4]) : $argv[4]) : 16;

$source = new Riimu\Kit\NumberConversion\NumberBase($sbase);
$target = new Riimu\Kit\NumberConversion\NumberBase($tbase);

$repeats = (int) $argv[1];
$length = (int) $argv[2];
$max = $source->getRadix() - 1;
$number = [mt_rand(1, $max)];

for ($i = 1; $i < $length; $i++) {
    $number[$i] = mt_rand(0, $max);
}

$number = $source->getDigits($number);

class TimeoutException extends RuntimeException { }

$timer = 0;

function ticker () {
    global $timer;

    if ((microtime(true) - $timer > 5.0)) {
        throw new TimeoutException();
    }
}

register_tick_function('ticker');
$common = null;

$doTrial = function ($class) use ($source, $target, $repeats, $number, & $timer, & $common) {
    $one = new $class($source, $target);
    $two = new $class($target, $source);
    $name = substr($class, 27);
    $timer = microtime(true);

    try {
        declare(ticks = 1) {
            for ($i = 0; $i < $repeats; $i++) {
                $mid = $one->convertNumber($number);
                $result = $two->convertNumber($mid);

                if ($result !== $number) {
                    throw new RuntimeException('Result does not match the original');
                } elseif ($common === null) {
                    $common = $mid;
                } elseif ($mid !== $common) {
                    throw new RuntimeException('Disperancy between mid results noticed');
                }
            }
        }
    } catch (TimeoutException $ex) {
        return print "timeout - $name" . PHP_EOL;
    } catch (ConversionException $ex) {
        return print "    N/A - $name" . PHP_EOL;
    }

    echo number_format(microtime(true) - $timer, 4) . "s - $name" . PHP_EOL;
};

echo "\nReplace Conversion:\n\n";

$doTrial('Riimu\Kit\NumberConversion\ConversionMethod\DirectReplaceConverter');
$doTrial('Riimu\Kit\NumberConversion\ConversionMethod\MathReplaceConverter');
$doTrial('Riimu\Kit\NumberConversion\ConversionMethod\NumberReplaceConverter');
$doTrial('Riimu\Kit\NumberConversion\ConversionMethod\StringReplaceConverter');

echo "\nDirect Conversion:\n\n";

$doTrial('Riimu\Kit\NumberConversion\ConversionMethod\NoveltyConverter');
$doTrial('Riimu\Kit\NumberConversion\ConversionMethod\DirectConverter');

echo "\nDecimal Conversion:\n\n";

$doTrial('Riimu\Kit\NumberConversion\DecimalConverter\GMPConverter');
$doTrial('Riimu\Kit\NumberConversion\DecimalConverter\BCMathConverter');
$doTrial('Riimu\Kit\NumberConversion\DecimalConverter\InternalConverter');

