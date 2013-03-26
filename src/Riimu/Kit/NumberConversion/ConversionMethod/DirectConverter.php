<?php

namespace Riimu\Kit\NumberConversion\ConversionMethod;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DirectConverter extends ConversionMethod
{
    use IntegerConstrained;

    /**
     * Converts numbers directly from base to another.
     *
     * Direct conversion converts the number by taking the decimal values of
     * the digits in the number and determining the reminder by using an
     * implementation of long division. Using this method, it is not required
     * to convert the entire number to decimal number in between which avoids the
     * limits of 32 bit integers. Due manual implementation of long division,
     * this tends to be quite a bit slower than decimal conversion using GMP
     * library. However, it is still a bit faster than decimal conversion using
     * BCMath library. Direct conversion, however, can only be used for the
     * integer part of numbers.
     *
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     */
    public function convertNumber(array $number)
    {
        $this->verifyIntegerConstraint();

        $number = $this->getDecimals($number);
        $sourceRadix = $this->source->getRadix();
        $targetRadix = $this->target->getRadix();
        $result = [];

        do {
            $first = true;
            $remainder = 0;

            foreach ($number as $i => $value) {
                $remainder = $value + $remainder * $sourceRadix;

                if ($remainder >= $targetRadix) {
                    $number[$i] = (int) ($remainder / $targetRadix);
                    $remainder = $remainder % $targetRadix;
                    $first = false;
                } elseif ($first) {
                    unset($number[$i]);
                } else {
                    $number[$i] = 0;
                }
            }

            $result[] = $remainder;
        } while (!empty($number));

        return $this->getDigits(array_reverse($result));
    }
}
