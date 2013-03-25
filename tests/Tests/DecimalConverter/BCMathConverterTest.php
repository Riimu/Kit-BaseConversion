<?php

namespace Tests\DecimalConverter;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BCMathConverterTest extends ConverterTestBase
{
    protected $className = 'Riimu\Kit\NumberConversion\DecimalConverter\BCMathConverter';

    public function setUp()
    {
        if (!function_exists('bcadd')) {
            $this->markTestSkipped('Missing BCMath extension');
        }
    }
}