<?php

namespace Rose\NumberConversion\DecimalConverter;

class BCMathConverter implements DecimalConverter
{
    public function ConvertNumber(array $number, $sourceRadix, $targetRadix)
    {
        $power = 0;
        $decimal = '0';
        $result = array();
        
        foreach (array_reverse($number) as $value) {
            $decimal = bcadd($decimal, bcmul($value, bcpow($sourceRadix, $power++)));
        }
        
        while ($decimal !== '0') {
            $modulo = bcmod($decimal, $targetRadix);
            $decimal = bcdiv(bcsub($decimal, $modulo), $targetRadix);
            $result[] = $modulo;
        }

        return empty($result) ? array('0') : array_reverse($result);
    }
}
