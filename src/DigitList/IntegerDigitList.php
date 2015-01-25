<?php

namespace Riimu\Kit\BaseConversion\DigitList;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015, Riikka Kalliomäki
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
     * @param integer $radix Radix for the numeral system
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

    private function buildArbitraryList($radix)
    {
        $format = '#%0' . strlen($radix - 1) . 'd';

        return array_map(function ($value) use ($format) {
            return sprintf($format, $value);
        }, range(0, $radix - 1));
    }
}
