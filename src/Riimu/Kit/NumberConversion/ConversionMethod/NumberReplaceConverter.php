<?php

namespace Riimu\Kit\NumberConversion\ConversionMethod;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class NumberReplaceConverter extends ReplaceConverter
{
    use ConversionTableBuilder;

    public function replace(array $number, $fractions = false)
    {
        $number = $this->zeroPad($this->getDecimals($number), $fractions, 0);
        $table = $this->getConversionTable();
        $log = max(1, log($this->target->getRadix(), $this->source->getRadix()));
        $replacements = [];

        foreach (array_chunk($number, $log) as $chunk) {
            $replacements[] = $table[implode(':', $chunk)];
        }

        $result = call_user_func_array('array_merge', $replacements);

        return $this->getDigits($this->zeroTrim($result, $fractions, 0));
    }

    protected function addItem(& $table, $sValues, $sDigits, $tValues, $tDigits)
    {
        $table[implode(':', $sValues)] = $tValues;
    }
}
