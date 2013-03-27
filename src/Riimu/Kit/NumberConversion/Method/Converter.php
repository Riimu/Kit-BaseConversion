<?php

namespace Riimu\Kit\NumberConversion\Method;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface Converter
{
    public function convertNumber(array $number);
    public function convertFractions(array $number);
}

class ConversionException extends \RuntimeException { }
