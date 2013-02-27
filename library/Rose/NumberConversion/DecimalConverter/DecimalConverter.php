<?php

namespace Rose\NumberConversion\DecimalConverter;

interface DecimalConverter
{
    public function ConvertNumber (array $number, $sourceRadix, $targetRadix);
}
