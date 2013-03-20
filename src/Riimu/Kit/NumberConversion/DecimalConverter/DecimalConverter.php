<?php

namespace Riimu\Kit\NumberConversion\DecimalConverter;

/**
 * Decimal converter converts numbers from radix to another using decimal logic.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface DecimalConverter
{
    /**
     * Converts number from radix to another using decimal logic.
     *
     * When called, the method is given the original number as an array, where
     * each value represents the decimal value of the digit in that position.
     * The least significant digit is in the first index. For example, a HEX
     * value of 'A09FF' would be given as [10, 0, 9, 15, 15]. The method will
     * then convert the number from the source base indicated by the source
     * radix to the target base indicated by the target radix and then return
     * it as an array similar to the input array. For example, the
     * aforementioned number coverted from radix 16 to radix 8 would be
     * returned as [2, 4, 0, 4, 7, 7, 7].
     *
     * @param array $number List of digit values
     * @param integer $sourceRadix Radix of the source base
     * @param integer $targetRadix Radix of the target base
     * @return array List of digit values for the converted number
     */
    public function ConvertNumber (array $number, $sourceRadix, $targetRadix);
}
