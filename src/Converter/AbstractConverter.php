<?php

namespace Riimu\Kit\NumberConversion\Converter;

use Riimu\Kit\NumberConversion\NumberBase;

/**
 * Abstract converters that implements basic functionality.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class AbstractConverter implements Converter
{
    /**
     * Numeral system for provided numbers.
     * @var \Riimu\Kit\NumberConversion\NumberBase
     */
    protected $source;

    /**
     * Numeral system for returned numbers.
     * @var \Riimu\Kit\NumberConversion\NumberBase
     */
    protected $target;

    /**
     * Initializes the abstract converter
     */
    public function __construct()
    {
        $this->source = null;
        $this->target = null;
    }

    public function setNumberBases(NumberBase $source, NumberBase $target)
    {
        $this->source = $source;
        $this->target = $target;
    }

    /**
     * Canonizes the number and returns it's decimal values in source base.
     * @param array $number Digits of the number
     * @return array Decimal values for the digits in the number
     */
    protected function getValues(array $number)
    {
        return empty($number) ? [0] : $this->source->getValues($number);
    }

    /**
     * Canonizes the number and returns the digits for the decimal values in target base.
     * @param array $number Decimal values to convert into digits
     * @return array Digits of the number based on the decimal values.
     */
    protected function getDigits(array $number)
    {
        return $this->target->getDigits(empty($number) ? [0] : $number);
    }
}