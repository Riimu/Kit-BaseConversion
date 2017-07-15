<?php

if ($argc < 4) {
    echo 'Usage: php convert.php <number> <source-base> <target-base> [precision]' . PHP_EOL;
    die;
}

require __DIR__ . '/../src/autoload.php';

$source = new Riimu\Kit\BaseConversion\NumberBase(is_numeric($argv[2]) ? (int) $argv[2] : $argv[2]);
$target = new Riimu\Kit\BaseConversion\NumberBase(is_numeric($argv[3]) ? (int) $argv[3] : $argv[3]);

$converter = new Riimu\Kit\BaseConversion\BaseConverter($source, $target);

if ($argc > 4) {
    $converter->setPrecision($argv[4]);
}

echo "In Base " . $source->getRadix() . ": $argv[1]" . PHP_EOL;
echo "In Base " . $target->getRadix() . ": " . $converter->convert($argv[1]) . PHP_EOL;
