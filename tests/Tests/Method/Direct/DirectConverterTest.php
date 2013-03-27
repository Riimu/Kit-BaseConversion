<?php

namespace Tests\Method\Direct;

use Tests\Method\ConverterTestBase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DirectConverterTest extends ConverterTestBase
{
    use IntegerConstrainedTraitTester;

    protected $className = 'Riimu\Kit\NumberConversion\Method\Direct\DirectConverter';
}
