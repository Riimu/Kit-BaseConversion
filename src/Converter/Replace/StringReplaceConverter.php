<?php

namespace Riimu\Kit\NumberConversion\Converter\Replace;

use Riimu\Kit\NumberConversion\Converter\ConversionException;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class StringReplaceConverter extends AbstractReplaceConverter
{
    use ConversionTableBuilderTrait { buildConversionTable as buildTable; }

    public function replace(array $number, $fractions = false)
    {
        $table = $this->getConversionTable();
        $digits = array_flip($this->source->getDigitList());

        // Verify and resolve case insensitivity
        foreach ($number as $digit) {
            if (!isset($digits[(string) $digit])) {
                $number = $this->source->getDigits($this->getValues($number));
                break;
            }
        }

        return $this->zeroTrim($this->target->splitString(
            strtr(implode('', $this->zeroPad($number, $fractions)), $table)
        ), $fractions);
    }

    public function getConversionTable()
    {
        if (!isset($this->conversionTable)) {
            $this->conversionTable = $this->buildConversionTable();
        }

        return $this->conversionTable;
    }

    protected function buildConversionTable()
    {
        if ($this->source->hasStringConflict() || $this->target->hasStringConflict()) {
            throw new ConversionException("Number bases must not have string conflicts");
        }

        return $this->buildTable();
    }

    protected function addItem(&$table, $sValues, $sDigits, $tValues, $tDigits)
    {
        $table[implode($sDigits)] = implode($tDigits);
    }
}
