<?php

namespace Riimu\Kit\NumberConversion\Converter;

/**
 * Interface for fraction converters.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface FractionConverter extends Converter
{
    /**
     * Converts the fraction from source base to target base.
     *
     * The digits of the fraction must be passed as an array with least
     * significant digit first. Depending on the number bases, the converter
     * may not be able to convert the fraction, in which case an exception is
     * thrown.
     *
     * @param array $number Digits of the fraction to convert
     * @return array Digits of the converted fraction
     * @throws ConversionException If this converter cannot convert the fraction
     */
    public function convertFractions(array $number);

    /**
     * Sets the precison for converted fractions.
     *
     * Positive value sets the maximum number of digits in converted fractions.
     * The result may contain a different number of digits if the converter
     * can accurately convert the fraction. If 0 is provided, the result will
     * contain as many digits as it takes to represent the provided fraction
     * in the target base with at least the same accuracy as the provided
     * fraction. The absolute value of negative number is simply added to this
     * number of digits.
     *
     * The last digit in the result is always rounded if the result is not
     * accurate.
     *
     * @param integer $precision Precision for converted fractions.
     */
    public function setPrecision($precision);
}
