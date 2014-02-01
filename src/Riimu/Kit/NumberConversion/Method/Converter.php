<?php

namespace Riimu\Kit\NumberConversion\Method;

use Riimu\Kit\NumberConversion\NumberBase;

/**
 * Base class for all the conversion strategies.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface Converter
{
    /**
     * Creates new converter.
     * @param NumberBase $sourceBase Conversion source base
     * @param NumberBase $targetBase Conversion target base
     */
    public function __construct(NumberBase $sourceBase, NumberBase $targetBase);

    /**
     * Converts the integer portion of the number
     * @param array $number Digits in the number with least significant digit first
     * @return array Digits of the number converted to new base
     * @throws ConversionException If this strategy cannot convert the number
     */
    public function convertNumber(array $number);

    /**
     * Converts the fraction portion of the number
     * @param array $number Digits in the number with least significant digit first
     * @return array Digits of the number converted to new base
     * @throws ConversionException If this strategy cannot convert the number
     */
    public function convertFractions(array $number);
}

/**
 * Exception thrown if the conversion strategy cannot convert the number.
 */
class ConversionException extends \RuntimeException { }
