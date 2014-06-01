<?php

namespace Riimu\Kit\BaseConversion;

/**
 * Interface for base converters.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface Converter
{
    /**
     * Sets the precision for inaccurate fraction conversions.
     *
     * If the precision is positive, it defines the maximum number of digits in
     * fractions. If the value is 0, the converted numbers have at least as many
     * digits as is required to represent the number in the same accuracy. A
     * negative precision simply increases the number of digits in addition to
     * what is required for same accuracy.
     *
     * The precision may be ignored if the converter can convert the fractions
     * accurately. The purpose of precision is to limit the number of digits in
     * cases where this is not possible.
     *
     * @param int $precision Precision used for inaccurate conversions.
     * @return void
     */
    public function setPrecision($precision);

    /**
     * Converts integer portion of a number.
     *
     * The number should be provided as an array with least significant digit
     * first. Any invalid digit in the array will cause an exception to be
     * thrown. The return value is a similar array of digits.
     *
     * @param array $number The number to convert to target base
     * @return array The number converted to target base
     * @throw \InvalidArgumentException If the number contains invalid digits
     */
    public function convertInteger(array $number);

    /**
     * Converts fraction portion of a number.
     *
     * The fractions should be provided as an array with least significant digit
     * first. Any invalid digit in the array will cause an exception to be
     * thrown. The return value is a similarly array of digits.
     *
     * @param array $number The fractions to convert to target base
     * @return array The fractions converted to target base
     * @throw \InvalidArgumentException If the fractions contain invalid digits.
     */
    public function convertFractions(array $number);
}
