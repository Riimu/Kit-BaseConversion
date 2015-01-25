<?php

namespace Riimu\Kit\BaseConversion\DigitList;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class StringDigitList extends AbstractDigitList
{
    /**
     * Creates a new instance of StringDigitList.
     * @param string $digits Digits for the numeral system
     * @throws \InvalidArgumentException If the list of digits is invalid
     */
    public function __construct($digits)
    {
        if (strlen($digits) < 2) {
            throw new \InvalidArgumentException('Number base needs at least 2 characters');
        } elseif (strlen(count_chars($digits, 3)) !== strlen($digits)) {
            throw new \InvalidArgumentException('Number base cannot have duplicate characters');
        }

        $this->digits = str_split($digits);
        $this->stringConflict = false;
        $this->setValueMap(array_flip($this->digits));
    }
}
