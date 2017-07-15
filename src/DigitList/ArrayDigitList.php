<?php

namespace Riimu\Kit\BaseConversion\DigitList;

/**
 * Handles a list of digits provided as an array.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ArrayDigitList extends AbstractDigitList
{
    /**
     * Creates a new instance of ArrayDigitList.
     *
     * The list of digits is provided as an array. The index provides value for
     * the digits and the values provide the digits themselves. Any kind of
     * value is an acceptable digit, but note that the digits are considered
     * duplicate if their values are equal using a loose comparison.
     *
     * @param array $digits The list of digits for the numeral system
     * @throws \InvalidArgumentException If the list of digits is invalid
     */
    public function __construct(array $digits)
    {
        $this->validateDigits($digits);

        $this->digits = $digits;

        $mapped = array_map('strval', array_filter($digits, [$this, 'isMapped']));
        $this->valueMap = array_flip($mapped);
        $this->stringConflict = count($mapped) === count($this->digits)
            ? $this->detectConflict($mapped, 'strpos') : true;

        $this->caseSensitive = $this->detectConflict($mapped, 'stripos');

        if (!$this->caseSensitive) {
            array_change_key_case($this->valueMap);
        }
    }

    /**
     * Validates and sorts the list of digits.
     * @param array $digits The list of digits for the numeral system
     * @throws \InvalidArgumentException If the digit list is invalid
     */
    private function validateDigits(& $digits)
    {
        ksort($digits);

        if (count($digits) < 2) {
            throw new \InvalidArgumentException('Number base must have at least 2 digits');
        } elseif (array_keys($digits) !== range(0, count($digits) - 1)) {
            throw new \InvalidArgumentException('Invalid digit values in the number base');
        } elseif ($this->detectDuplicates($digits)) {
            throw new \InvalidArgumentException('Number base cannot have duplicate digits');
        }
    }

    /**
     * Tells if the list of digits has duplicate values.
     * @param array $digits The list of digits for the numeral system
     * @return bool True if the list contains duplicate digits, false if not
     */
    private function detectDuplicates(array $digits)
    {
        for ($i = count($digits); $i > 0; $i--) {
            if (array_search(array_pop($digits), $digits) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tells if the digit can be mapped using a value map.
     * @param mixed $digit The digit to test
     * @return bool True if the digit can be mapped, false if not
     */
    private function isMapped($digit)
    {
        return is_string($digit) || is_int($digit);
    }

    /**
     * Tells if a conflict exists between string values.
     * @param string[] $digits The list of digits for the numeral system
     * @param callable $detect Function used to detect the conflict
     * @return bool True if a conflict exists, false if not
     */
    private function detectConflict(array $digits, callable $detect)
    {
        foreach ($digits as $digit) {
            if ($this->inDigits($digit, $digits, $detect)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tells if a conflict exists for a digit in a list of digits.
     * @param string $digit A single digit to test
     * @param string[] $digits The list of digits for the numeral system
     * @param callable $detect Function used to detect the conflict
     * @return bool True if a conflict exists, false if not
     */
    private function inDigits($digit, array $digits, callable $detect)
    {
        foreach ($digits as $haystack) {
            if ($digit !== $haystack && $detect($haystack, $digit) !== false) {
                return true;
            }
        }

        return false;
    }

    public function getValue($digit)
    {
        if ($this->isMapped($digit)) {
            return parent::getValue($digit);
        }

        $value = array_search($digit, $this->digits);

        if ($value === false) {
            throw new InvalidDigitException('Invalid digit');
        }

        return $value;
    }
}
