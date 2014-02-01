<?php

set_include_path(__DIR__ . '/../src');
spl_autoload_register();

$converter = new Riimu\Kit\NumberConversion\BaseConverter(10, 12);
$converter->setPrecision(5);
echo $converter->convert('-1337.1337') . PHP_EOL; // Outputs '-935.17305'

$converter->setPrecision(10);
echo $converter->convert('-1337.1337') . PHP_EOL; // Outputs '-935.17304A0891'

$converter->setPrecision(4);
echo $converter->convert('-1337.1337') . PHP_EOL; // Outputs '-935.1730'

$test = new Riimu\Kit\NumberConversion\BaseConverter(10, 2);
echo $test->convert('4503599627370495') . PHP_EOL;