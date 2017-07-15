<?php

namespace Riimu\Kit\BaseConversion;

/**
 * Interface for different number base conversion strategies.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface Converter
{
    /**
     * Sets the precision for inaccurate fraction conversions.
     *
     * The fractions cannot always be accurately converted from base to another,
     * since they may represent a fraction that cannot be represented in another
     * number base. The precision value is used to determine the number of
     * digits in the fractional part, if the number cannot be accurately
     * converted or if it is not feasible to determine that.
     *
     * If the precision is positive, it defines the maximum number of digits in
     * the fractional part. If the value is 0, the converted number will have
     * at least as many digits in the fractional part as it takes to represent
     * the number in the same accuracy as the original number. A negative number
     * will simply increase the number of digits.
     *
     * Note that the fractional part may have fewer digits than what is required
     * by the precision if it can be accurately represented using fewer digits.
     *
     * @param int $precision Precision used for inaccurate conversions
     * @return void
     */
    public function setPrecision($precision);

    /**
     * Converts the integer part of a number.
     *
     * The integer part should be provided as an array of digits with least
     * significant digit first. Any invalid digit in the array will cause an
     * exception to be thrown. The return value will be a similar array of
     * digits, except converted to the target number base.
     *
     * @param array $number Array of digits representing the integer part
     * @return array Digits for the converted number
     * @throws DigitList\InvalidDigitException If the integer part contains invalid digits
     */
    public function convertInteger(array $number);

    /**
     * Converts the fractional part of a number.
     *
     * The fractional part should be provided as an array of digits with least
     * significant digit first. Any invalid digit in the array will cause an
     * exception to be thrown. The return value will be a similar array of
     * digits, except converted to the target number base.
     *
     * @param array $number Array of digits representing the fractional part
     * @return array Digits for the converted number
     * @throws DigitList\InvalidDigitException If the fractional part contain invalid digits
     */
    public function convertFractions(array $number);
}
