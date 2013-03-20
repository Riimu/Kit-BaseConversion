<?php

namespace Riimu\Kit\NumberConversion\DecimalConverter;

/**
 * Provides decimal conversion using BCMath functions.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BCMathConverter implements DecimalConverter
{
    public function ConvertNumber(array $number, $sourceRadix, $targetRadix)
    {
        $power = 0;
        $decimal = '0';
        $result = [];

        foreach (array_reverse($number) as $value) {
            $decimal = bcadd($decimal, bcmul($value,
                bcpow($sourceRadix, $power++, 0), 0), 0);
        }

        while ($decimal !== '0') {
            $modulo = bcmod($decimal, $targetRadix);
            $decimal = bcdiv($decimal, $targetRadix, 0);
            $result[] = (int) $modulo;
        }

        return empty($result) ? [0] : array_reverse($result);
    }
}
