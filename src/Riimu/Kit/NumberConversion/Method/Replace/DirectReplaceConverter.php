<?php

namespace Riimu\Kit\NumberConversion\Method\Replace;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DirectReplaceConverter extends AbstractReplaceConverter
{
    use ConversionTableBuilderTrait;

    public function replace(array $number, $fractions = false)
    {
        $table = $this->getConversionTable();
        $size = count($table[0][0]);
        $number = $this->zeroPad($number, $fractions);
        $replacements = [];

        foreach (array_chunk($number, $size) as $chunk) {
            $key = array_search($chunk, $table[0]);

            // Attempt to resolve case insensitivity
            if ($key === false) {
                $chunk = $this->source->getDigits($this->getDecimals($chunk));
                $key = array_search($chunk, $table[0]);
            }

            $replacements[] = $table[1][$key];
        }

        $result = call_user_func_array('array_merge', $replacements);

        return $this->zeroTrim($result, $fractions);
    }

    protected function addItem(&$table, $sValues, $sDigits, $tValues, $tDigits)
    {
        $table[0][] = $sDigits;
        $table[1][] = $tDigits;
    }
}
