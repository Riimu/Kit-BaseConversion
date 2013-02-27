<?php

namespace Rose\NumberConversion\DecimalConverter;

class GMPConverter implements DecimalConverter
{
    public function ConvertNumber(array $number, $sourceRadix, $targetRadix)
    {
        $sourceRadix = gmp_init($sourceRadix);
        $targetRadix = gmp_init($targetRadix);
        $decimal = gmp_init(0);
        $result = array();
        $power = 0;
        
        foreach (array_reverse($number) as $value) {
            $decimal = gmp_add($decimal, gmp_mul($value, gmp_pow($sourceRadix, $power++)));
        }
        
        while (gmp_cmp($decimal, '0') != 0) {
            list($decimal, $modulo) = gmp_div_qr($decimal, $targetRadix);
            $result[] = gmp_strval($modulo);
        }

        return empty($result) ? array('0') : array_reverse($result);
    }
}
