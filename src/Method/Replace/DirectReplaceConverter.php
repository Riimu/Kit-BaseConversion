<?php

namespace Riimu\Kit\NumberConversion\Method\Replace;
    /**
     * Converts number from base to another by simply replacing the numbers.
     *
     * If the radix of either number base is nth root for the other base, then
     * conversion can be performed by simply replacing the digits with digits
     * from the target base. No calculation logic is required, which makes this
     * the fastest conversion method by far. A slight overhead is caused on the
     * first conversion by generation of the number conversion table. An
     * exception is thrown if replacement conversion cannot be performed between
     * the two number bases.
     *
     * @param array $number Number to covert with most significant digit last
     * @param boolean $fractions True if converting fractions, false if not
     * @return array The converted number with most significant digit last
     * @throws \InvalidArgumentException if replacement conversion is not possible
     */
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
