<?php

namespace Tests\DecimalConverter;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class GMPConverterTest extends ConverterTestBase
{
    protected $className = 'Riimu\Kit\NumberConversion\DecimalConverter\GMPConverter';
    
    public function setUp()
    {
        if (!function_exists('gmp_add')) {
            $this->markTestSkipped('Missing GMP extension');
        }
    }
}
