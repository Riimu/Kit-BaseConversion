<?php

namespace Riimu\Kit\NumberConversion\Converter\Decimal;

/**
 * Provides decimal conversion using GMP functions.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class GMPConverter extends AbstractDecimalConverter
{
    public function isSupported()
    {
        return function_exists('gmp_add');
    }

    protected function init($number)
    {
        return gmp_init($number);
    }

    protected function val($number)
    {
        return gmp_strval($number);
    }

    protected function add($a, $b)
    {
        return gmp_add($a, $b);
    }

    protected function mul($a, $b)
    {
        return gmp_mul($a, $b);
    }

    protected function pow($a, $b)
    {
        return gmp_pow($a, $b);
    }

    protected function div($a, $b)
    {
        return gmp_div_qr($a, $b);
    }

    protected function cmp($a, $b)
    {
        return gmp_cmp($a, $b);
    }
}
