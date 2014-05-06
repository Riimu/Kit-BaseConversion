<?php

namespace Riimu\Kit\NumberConversion\Converter\Direct;

use Riimu\Kit\NumberConversion\Converter\IntegerConverter;
use Riimu\Kit\NumberConversion\Converter\AbstractConverter;

/**
 * Provides direct conversion strategy for integers.
 *
 * Direct conversion converts the number by taking the decimal values of
 * the digits in the number and determining the reminder by using an
 * implementation of long division. Using this method, it is not required
 * to convert the entire number to decimal number in between which avoids the
 * limits of 32 bit integers. The manual implementation of long division does
 * not make this particular fast, strategy, though. This strategy only works
 * for integers, however. Direct conversion is also limited to rather small
 * number bases in order to avoid integer overflows during the long division.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DirectConverter extends AbstractConverter implements IntegerConverter
{
    use IntegerConstrainedTrait;

    /**
     * Converts numbers directly from base to another.
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     */
    public function convertInteger(array $number)
    {
        $this->verifyIntegerConstraint();

        $number = $this->getValues($number);
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
