<?php

namespace Riimu\Kit\NumberConversion\Converter\Direct;

use Riimu\Kit\NumberConversion\Converter\ConverterTestBase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DirectConverterTest extends ConverterTestBase
{
    use IntegerConstrainedTraitTester;

    protected $className = 'Riimu\Kit\NumberConversion\Converter\Direct\DirectConverter';
}
