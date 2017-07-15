<?php

namespace Riimu\Kit\BaseConversion\DigitList;

/**
 * Handles a list of digits defined according to number base.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class IntegerDigitList extends AbstractDigitList
{
    /** @var string List of digits for bases smaller than 63 */
    private static $integerBase = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /** @var string List of digits for base 64 */
    private static $integerBase64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

    /**
     * Creates a new instance of IntegerDigitList.
     *
     * The digit list is defined by giving the radix (i.e. base) for the number
     * system that defines the number of digits in the digit list. The actual
     * digits are determined based on the given number.
     *
     * If the given radix is 62 or less, the digits from the list 0-9A-Za-z
     * are used. The digits are case insensitive, if the radix is 36 or less.
     *
     * If the radix is 64, then the digits from the base64 standard are used.
     * Base64 is always case sensitive.
     *
     * If the radix is 63 or 65 to 256, then digits are represented by a single
     * byte with equal byte value.
     *
     * If the radix is 257 or greater, then each digit is represented by a
     * string of #NNN (where NNN is the value of the digit). Each string has
     * equal length, which depends on the given radix.
     *
     * @param int $radix Radix for the numeral system
     * @throws \InvalidArgumentException If the radix is invalid
     */
    public function __construct($radix)
    {
        $radix = (int) $radix;

        if ($radix < 2) {
            throw new \InvalidArgumentException('Invalid radix');
        }

        $this->digits = $this->buildDigitList($radix);
        $this->stringConflict = false;
        $this->setValueMap(array_flip($this->digits));
    }

    /**
     * Builds the list of digits according to the radix.
     * @param int $radix Radix for the numeral system
     * @return string[] The list of digits
     */
    private function buildDigitList($radix)
    {
        if ($radix <= strlen(self::$integerBase)) {
            return str_split(substr(self::$integerBase, 0, $radix));
        } elseif ($radix === 64) {
            return str_split(self::$integerBase64);
        } elseif ($radix <= 256) {
            return range(chr(0), chr($radix - 1));
        }

        return $this->buildArbitraryList($radix);
    }

    /**
     * Builds the list of digits for arbitrary radix.
     * @param int $radix Radix for the numeral system
     * @return string[] The list of digits
     */
    private function buildArbitraryList($radix)
    {
        $format = '#%0' . strlen($radix - 1) . 'd';

        return array_map(function ($value) use ($format) {
            return sprintf($format, $value);
        }, range(0, $radix - 1));
    }
}
