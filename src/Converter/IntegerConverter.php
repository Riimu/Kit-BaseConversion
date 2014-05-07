<?php

namespace Riimu\Kit\NumberConversion\Converter;

/**
 * Interface for integer converters.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface IntegerConverter extends Converter
{
    /**
     * Converts the integer from source base to target base.
     *
     * The digits of the integer must be passed as an array with least
     * significant digit first. Depending on the number bases, the converter
     * may not be able to convert the integer, in which case an exception is
     * thrown.
     *
     * @param array $number Digits of the integer to convert
     * @return array Digits of the converted integer
     * @throws ConversionException If this converter cannot convert the integer
     */
    public function convertInteger(array $number);
}
