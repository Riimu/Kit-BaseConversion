<?php

if ($argc < 4) {
    echo "Usage: convert <number> <source-base> <target-base> [precision]" . PHP_EOL;
    die;
}

set_include_path(__DIR__ . '/../src');
spl_autoload_register();

use Riimu\Kit\NumberConversion\BaseConverter;
use Riimu\Kit\NumberConversion\NumberBase;

$source = new NumberBase(is_numeric($argv[2]) ? (int) $argv[2] : $argv[2]);
$target = new NumberBase(is_numeric($argv[3]) ? (int) $argv[3] : $argv[3]);

$converter = new BaseConverter($source, $target);

if ($argc > 4) {
    $converter->setPrecision($argv[4]);
}

echo "In Base " . $source->getRadix() . ": $argv[1]" . PHP_EOL;
echo "In Base " . $target->getRadix() . ": " . $converter->convert($argv[1]) . PHP_EOL;
