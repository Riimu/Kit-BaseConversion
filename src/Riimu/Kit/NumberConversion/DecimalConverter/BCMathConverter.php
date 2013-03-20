<?php

namespace Riimu\Kit\NumberConversion\DecimalConverter;

/**
 * Provides decimal conversion using BCMath functions.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BCMathConverter extends DecimalConverter
{
    public function convertNumber(array $number, $sourceRadix, $targetRadix)
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

    public function convertFraction(array $number, $sourceRadix, $targetRadix, $precision = -1)
    {
        $maxFraction = bcpow($sourceRadix, count($number));
        $decimal = '0';
        $power = 1;

        if ($precision < 1) {
            $digits = 0;
            do {
                $digits += 1;
            } while(bccomp(bcpow($targetRadix, $digits, 0), $maxFraction, 0) <= 0);
            $digits += abs($precision);
        } else {
            $digits = $precision;
        }

        $maxResultFraction = bcpow($targetRadix, $digits, 0);
        $decimalDigits = max(strlen($maxFraction), strlen($maxResultFraction)) + 2;

        foreach ($number as $value) {
            $fraction = bcpow($sourceRadix, $power++, 0);
            $quotient = bcdiv($value, $fraction, $decimalDigits);
            $decimal = bcadd($decimal, $quotient, $decimalDigits);
        }

        $result = [];

        for ($i = 0; $i <= $digits; $i++) {
            $decimal = bcmul($decimal, $targetRadix, $decimalDigits);
            $result[] = (int) $decimal;
            $decimal = substr_replace($decimal, 0, 0, strpos($decimal, '.'));
        }

        if (array_pop($result) >= $targetRadix / 2) {
            $i = count($result) - 1;
            for ($result[$i] += 1; $i >= 0 && $result[$i] == $targetRadix; $i--) {
                $result[$i] = 0;

                if ($i > 0) {
                    $result[$i - 1] += 1;
                }
            }
        }

        while (end($result) === 0) {
            array_pop($result);
        }

        return $result;
    }

    protected function init($number)
    {
        return (string) $number;
    }

    protected function val($number)
    {
        return (string) $number;
    }

    protected function add($a, $b)
    {
        return bcadd($a, $b, 0);
    }

    protected function mul($a, $b)
    {
        return bcmul($a, $b, 0);
    }

    protected function pow($a, $b)
    {
        return bcpow($a, $b, 0);
    }

    protected function div($a, $b)
    {
        return [bcdiv($a, $b, 0), bcmod($a, $b)];
    }

    protected function cmp($a, $b)
    {
        return bccomp($a, $b);
    }
}
