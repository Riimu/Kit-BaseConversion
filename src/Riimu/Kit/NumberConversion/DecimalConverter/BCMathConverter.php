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
