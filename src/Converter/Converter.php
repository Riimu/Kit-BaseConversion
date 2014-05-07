<?php

namespace Riimu\Kit\NumberConversion\Converter;

use Riimu\Kit\NumberConversion\NumberBase;

/**
 * Base interface for all number converters.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface Converter
{
    /**
     * Sets the source and target numeral systems for conversions.
     * @param \Riimu\Kit\NumberConversion\NumberBase $source Numeral system for provided numbers
     * @param \Riimu\Kit\NumberConversion\NumberBase $target Numeral system for returned numbers
     */
    public function setNumberBases(NumberBase $source, NumberBase $target);
}
