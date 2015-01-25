<?php

namespace Riimu\Kit\BaseConversion\DigitList;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ArrayDigitList extends AbstractDigitList
{
    /**
     * Creates a new instance of ArrayDigitList.
     * @param array $array Digits for the numeral system.
     * @throws \InvalidArgumentException If too few or duplicate values exist or indexes are not valid
     */
    public function __construct(array $digits)
    {
        $this->validateDigits($digits);

        $this->digits = $digits;
        $this->valueMap = array_flip(array_filter($digits, [$this, 'isMapped']));
        $this->stringConflict = count($this->valueMap) === count($this->digits)
            ? $this->detectConflict($this->digits, 'strpos') : true;

        $this->caseSensitive = $this->detectConflict(array_flip($this->valueMap), 'stripos');

        if (!$this->caseSensitive) {
            array_change_key_case($this->valueMap);
        }
    }

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

    private function detectDuplicates(array $digits)
    {
        $list = [];

        foreach ($digits as $digit) {
            if (array_search($digit, $list) !== false) {
                return true;
            }

            $list[] = $digit;
        }

        return false;
    }

    private function isMapped($digit)
    {
        return is_string($digit) || is_int($digit);
    }

    private function detectConflict(array $digits, callable $detect)
    {
        $digits = array_map('strval', $digits);

        foreach ($digits as $digit) {
            if ($this->inDigits($digit, $digits, $detect)) {
                return true;
            }
        }

        return false;
    }

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
        $key = array_search($digit, $this->digits);
        return $key === false ? parent::getValue($digit) : $key;
    }
}
