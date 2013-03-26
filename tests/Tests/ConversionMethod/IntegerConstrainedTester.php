<?php

namespace Tests\ConversionMethod;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
trait IntegerConstrainedTester
{
    /**
     * @expectedException Riimu\Kit\NumberConversion\ConversionMethod\ConversionException
     */
    public function testIntegerConstraintment()
    {
        $conv = $this->getConverter(131072, 131073);
        $conv->convertNumber([1]);
    }
}
