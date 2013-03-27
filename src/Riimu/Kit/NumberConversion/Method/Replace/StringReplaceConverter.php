<?php

namespace Riimu\Kit\NumberConversion\Method\Replace;

use Riimu\Kit\NumberConversion\Method\ConversionException;

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
        $digits = array_flip($this->source->getNumbers());

        // Verify and resolve case insensitivity
        foreach ($number as $digit) {
            if (!isset($digits[(string) $digit])) {
                $number = $this->source->getDigits($this->getDecimals($number));
                break;
            }
        }

        return $this->zeroTrim(str_split(
            strtr(implode('', $this->zeroPad($number, $fractions)), $table),
            strlen($this->target->getDigit(0))
        ), $fractions);
    }

    public function getConversionTable()
    {
        if (!isset($this->conversionTable)) {
            $this->conversionTable = $this->buildConversionTable();
        }

        return $this->conversionTable;
    }

    private function buildConversionTable()
    {
        if (!$this->source->isStatic() || !$this->target->isStatic()) {
            throw new ConversionException("Both number bases are not static");
        }

        return $this->buildTable();
    }

    protected function addItem(&$table, $sValues, $sDigits, $tValues, $tDigits)
    {
        $table[implode($sDigits)] = implode($tDigits);
    }
}
