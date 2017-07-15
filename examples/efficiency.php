<?php

if ($argc < 3) {
    echo 'Usage php efficiency.php <repeats> <length> [source-base] [target-base]' . PHP_EOL;
    exit;
}

require __DIR__ . '/../src/autoload.php';

echo 'Test for efficiency of different algorithms available:' . PHP_EOL;

$sbase = isset($argv[3]) ? (ctype_digit($argv[3]) ? intval($argv[3]) : $argv[3]) : 2;
$tbase = isset($argv[4]) ? (ctype_digit($argv[4]) ? intval($argv[4]) : $argv[4]) : 16;

$source = new Riimu\Kit\BaseConversion\NumberBase($sbase);
$target = new Riimu\Kit\BaseConversion\NumberBase($tbase);

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
    $name = substr($class, 25);
    $timer = microtime(true);

    try {
        declare(ticks = 1) {
            for ($i = 0; $i < $repeats; $i++) {
                $mid = $one->convertInteger($number);
                $result = $two->convertInteger($mid);

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
    }

    echo number_format(microtime(true) - $timer, 4) . "s - $name" . PHP_EOL;
};

echo "\nReplace Conversion:\n\n";

$doTrial(\Riimu\Kit\BaseConversion\ReplaceConverter::class);
$doTrial(\Riimu\Kit\BaseConversion\DecimalConverter::class);

