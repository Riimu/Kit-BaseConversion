<?php

namespace Riimu\Kit\NumberConversion\ConversionMethod;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class NoveltyConverter extends ConversionMethod
{
    public function convertNumber(array $number)
    {
        $result = self::convert(
            $this->getDecimals($number),
            range(0, $this->source->getRadix() - 1),
            range(0, $this->target->getRadix() - 1)
        );

        if ($result === false) {
            throw new \InvalidArgumentException("Invalid number provided");
        }

        return $this->getDigits($result);
    }

    /**
     * Converts integers directly from base to another with minimal overhead.
     *
     * Any of the arguments may be provided as a string or an array with the
     * least significant digit first. For example, using 'A09FF' as the number,
     * '0123456789ABCDEF' as the source base and '01234567' as the target base
     * will return '2404777'. The method will return a string or an array
     * depending on the type of the input number.
     *
     * The logic of this method is essentially the same as convertDirectly(),
     * except that there is no function call overhead as the method only uses
     * language constructs. This makes it slightly faster than
     * convertDirectly(), but it does not take advantage of the NumberBase
     * class. This method exists mostly for vanity reasons providing a silly
     * example of using strings and arrays interchangeably.
     *
     * @param string|array $number The number to convert
     * @param string|array $sourceBase The number base for the original number
     * @param string|array $targetBase The number base for the resulting number
     * @return string|array|false Resulted number or false on error
     */
    public static function convert ($number, $sourceBase, $targetBase)
    {
        for ($sourceRadix = 0; isset($sourceBase[$sourceRadix]); $sourceRadix++) {
            $sourceMap[$sourceBase[$sourceRadix]] = $sourceRadix;
        }

        for ($targetRadix = 0; isset($targetBase[$targetRadix]); $targetRadix++);

        $numbers = [];

        for ($numberLength = 0; isset($number[$numberLength]); $numberLength++) {
            if (!isset($sourceMap[$number[$numberLength]])) {
                return false;
            }

            $numbers[$numberLength] = $sourceMap[$number[$numberLength]];
        }

        if ($sourceRadix < 2 || $targetRadix < 2) {
            return false;
        } elseif ($numberLength < 1) {
            return $targetBase[0];
        }

        $result = [];
        $resultLength = 0;
        $skip = 0;

        do {
            $remainder = 0;
            $first = true;

            for ($i = $skip; $i < $numberLength; $i++) {
                $remainder = $numbers[$i] + $remainder * $sourceRadix;

                if ($remainder >= $targetRadix) {
                    $numbers[$i] = (int) ($remainder / $targetRadix);
                    $remainder = $remainder % $targetRadix;
                    $first = false;
                } elseif ($first) {
                    $skip++;
                } else {
                    $numbers[$i] = 0;
                }
            }

            $result[$resultLength++] = $targetBase[$remainder];
        } while ($skip < $numberLength);

        // Essentially is_string() using language construct
        $test = $number;
        $test[0] = '';
        $return = $test[0] === '' ? [] : ' ';

        for ($i = 0; $i < $resultLength; $i++) {
            $return[$i] = $result[$resultLength - $i - 1];
        }

        return $return;
    }
}
