<?php

namespace Riimu\Kit\NumberConversion\Method;

use Riimu\Kit\NumberConversion\NumberBase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class AbstractConverter implements Converter
{
    protected $source;
    protected $target;

    public function __construct(NumberBase $sourceBase, NumberBase $targetBase)
    {
        $this->source = $sourceBase;
        $this->target = $targetBase;
    }

    public function convertNumber(array $number)
    {
        throw new ConversionException("This converter does not support number conversion");
    }

    public function convertFractions(array $number)
    {
        throw new ConversionException("This converter does not support fraction conversion");
    }

    protected function getDecimals(array $number)
    {
        return empty($number) ? [0] : $this->source->getDecimals($number);
    }

    protected function getDigits(array $number)
    {
        return $this->target->getDigits(empty($number) ? [0] : $number);
    }
}
