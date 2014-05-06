<?php

require __DIR__ . '/../vendor/autoload.php';

$loader = new \Riimu\Kit\ClassLoader\ClassLoader();
$loader->addPrefixPath(__DIR__ . '/../src', 'Riimu\Kit\NumberConversion');
$loader->addPrefixPath(__DIR__ . '/Tests', 'Riimu\Kit\NumberConversion');

$loader->register();

?>
