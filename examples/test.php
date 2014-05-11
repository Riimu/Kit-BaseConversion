<?php

require __DIR__ . '/../src/Converter.php';
require __DIR__ . '/../src/NumberBase.php';
require __DIR__ . '/../src/DecimalConverter.php';
require __DIR__ . '/../src/ReplaceConverter.php';
require __DIR__ . '/../src/BaseConverter.php';

$converter = new Riimu\Kit\BaseConversion\BaseConverter(10, 16);
echo $converter->convert('42') .  PHP_EOL; // Will output '2A'

$converter = new Riimu\Kit\BaseConversion\BaseConverter(8, 12);
echo $converter->convert('-1337.1337') .  PHP_EOL; // Will output '-513.21A0B'

$converter = new Riimu\Kit\BaseConversion\BaseConverter(10, 12);
$converter->setPrecision(5);
echo $converter->convert('-1337.1337') . PHP_EOL; // Outputs '-935.17304'

$converter->setPrecision(10);
echo $converter->convert('-1337.1337') . PHP_EOL; // Outputs '-935.17304A0890'

$converter->setPrecision(4);
echo $converter->convert('-1337.1337') . PHP_EOL; // Outputs '-935.1730'

$converter = new Riimu\Kit\BaseConversion\BaseConverter(15, 2);
echo $converter->convert('0.A7') . PHP_EOL; // Outputs '0.101100101'

$converter->setPrecision(-3);
echo $converter->convert('0.A7') . PHP_EOL; // Outputs '0.10110010101'

$converter->setPrecision(-10);
echo $converter->convert('0.A7') . PHP_EOL; // Outputs '0.101100101010000110'

$converter = new Riimu\Kit\BaseConversion\BaseConverter(10, '0123456789abcdef');
echo $converter->convert('42'); // Will output '2a'