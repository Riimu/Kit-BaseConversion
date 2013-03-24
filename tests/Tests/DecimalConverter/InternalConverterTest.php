<?php

namespace Tests\DecimalConverter;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class InternalConverterTest extends ConverterTestBase
{
    public function createConverter()
    {
        return new \Riimu\Kit\NumberConversion\DecimalConverter\InternalConverter();
    }
}
