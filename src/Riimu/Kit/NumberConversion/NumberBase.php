<?php

namespace Riimu\Kit\NumberConversion;

/**
 * NumberBase handles digit representations in number systems.
 *
 * The number base can be defined in multiple different ways. For more
 * information, see the details on the constructor. While NumberBase cares
 * little about the type of digit representations, it is worth noting that all
 * comparisons are done using loose comparison operators. Additionally,
 * NumberBase handles strings in case insensitive manner whenever possible (i.e.
 * it does not create conflicting digits).
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class NumberBase
{
    /**
     * The amount of digits in the number system.
     * @var integer
     */
    private $radix;

    /**
     * List of different digits in the number system and their decimal values.
     * @var array
     */
    private $numbers;

    private $valueMap;
    private $caseSensitive;

    /**
     * List of digits used when the base is provided as a number.
     * @var string
     */
    private static $integerBase =
        '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * List of digits to use when base 64 is used.
     * @var string
     */
    private static $integerBase64 =
        'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

    /**
     * Creates a new number base.
     *
     * The number base may be provided either as an array, string or an integer.
     *
     * If an integer is provided, then the digits in the number base
     * depend on the value of the integer. For bases of 62 and smaller, digits
     * are used fom series of 0-9A-Za-z. If the base is exactly 64, the
     * characters from base 64 encoding are used. For bases smaller or equal
     * to 256 the digits are represented by bytes of equal value. If the base is
     * bigger than 256, then each digit is represented by #num; where 'num' is
     * replace by the decimal value of the digit.
     *
     * If a string is provided as the number base, then characters in that
     * string are used as the digits and their position in the string determine
     * their decimal value. For example hexadecimal base would be given as
     * '0123456789ABCDEF'. Duplicate characters in the number base will cause an
     * exception.
     *
     * If an array is provided as the number base, then values in the array are
     * used as the digits and their indexes as their decimal values. Any type
     * of values may be used, but duplicate values (with loose comparison) will
     * cause an exception. Any missing decimal value in the indexes will also
     * cause an exception.
     *
     * @param integer|string|array $numberBase The number base to use
     * @throws \InvalidArgumentException If the given number base is invalid
     */
    public function __construct ($numberBase)
    {
        if (is_int($numberBase) || is_float($numberBase)) {
            $this->setBaseInteger($numberBase);
        } elseif (is_string($numberBase)) {
            $this->setBaseString($numberBase);
        } elseif (is_array($numberBase)) {
            $this->setBaseArray($numberBase);
        } else {
            throw new \InvalidArgumentException('Unexpected number base type');
        }
    }

    /**
     * Sets the digits according to the given size of the number base.
     * @param integer $integer Radix for the number base
     * @throws \InvalidArgumentException If the radix is too small
     */
    private function setBaseInteger ($integer)
    {
        $integer = (int) $integer;

        if ($integer < 2) {
            throw new \InvalidArgumentException('Radix must be bigger than 2');
        }

        $this->numbers = [];

        if ($integer <= strlen(self::$integerBase)) {
            $this->numbers = str_split(substr(self::$integerBase, 0, $integer));
            $this->caseSensitive = $integer > strpos(self::$integerBase, 'a');
        } elseif ($integer == 64) {
            $this->numbers = str_split(self::$integerBase64);
            $this->caseSensitive = true;
        } elseif ($integer <= 256) {
            for ($i = 0; $i < $integer; $i++) {
                $this->numbers[] = chr($i);
            }
        } else {
            for ($i = 0; $i < $integer; $i++) {
                $this->numbers[] = "#$i;";
            }
            $this->caseSensitive = false;
        }

        $this->valueMap = array_flip($this->numbers);
        $this->radix = count($this->numbers);
    }

    /**
     * Uses the characters in the given string as digits in the number base.
     * @param string $string Digits for the number system
     * @throws \InvalidArgumentException If there are too few or duplicate characters
     */
    private function setBaseString ($string)
    {
        if (strlen($string) < 2) {
            throw new \InvalidArgumentException("Number base needs at least 2 characters");
        } elseif (array_keys(array_flip(count_chars($string, 1))) !== [1]) {
            throw new \InvalidArgumentException("Duplicate characters in number base");
        }

        $this->numbers = str_split($string);
        $this->valueMap = array_flip($this->numbers);
        $this->radix = count($this->numbers);
        $this->caseSensitive =
            count(array_flip(array_map('strtolower', $this->numbers))) != $this->radix;
    }

    /**
     * Uses the values in the array to represent the digits.
     * @param array $array Digits for the number system.
     * @throws \InvalidArgumentException If too few or duplicate values exist or indexes are not valid
     */
    private function setBaseArray (array $array)
    {
        if (count($array) < 2) {
            throw new \InvalidArgumentException('Number base must have at least 2 values');
        }

        $numbers = [];
        $strings = [];
        $mapped = true;

        foreach ($array as $key => $value) {
            if (array_search($value, $numbers) !== false) {
                throw new \InvalidArgumentException('Duplicate values in number base');
            }

            $numbers[$key] = $value;

            if (is_string($value)) {
                $strings[] = strtolower($value);
            } elseif (!is_int($value)) {
                $mapped = false;
            }
        }

        $keys = array_keys($numbers);
        sort($keys);

        // Sorted array from 0 to n should have identical keys and values
        if (array_flip($keys) !== $keys) {
            throw new \InvalidArgumentException('Invalid indexes in the number base');
        }

        $this->radix = count($numbers);
        $this->numbers = $numbers;
        $this->valueMap = $mapped ? array_flip($numbers) : null;
        $this->caseSensitive = count($strings) != count(array_flip($strings));
    }

    /**
     * Returns the radix (i.e. size) of the number system.
     * @return integer Radix of the number base
     */
    public function getRadix ()
    {
        return $this->radix;
    }

    public function getNumbers()
    {
        return $this->numbers;
    }

    /**
     * Tells if the given digit is part of this number system.
     * @param mixed $digit The digit to look up
     * @return boolean True if the digit exists, false is not
     */
    public function hasDigit($digit)
    {
        return $this->findDigit($digit) !== false;
    }

    /**
     * Returns the decimal value represented by the given digit.
     * @param mixed $digit The digit to look up
     * @return integer The decimal value for the provided digit
     * @throws \InvalidArgumentException If the given digit does not exist
     */
    public function getDecimal($digit)
    {
        return $this->getDecimals([$digit])[0];
    }

    public function getDecimals(array $digits)
    {
        $decimals = [];

        foreach ($digits as $digit) {
            if ($this->valueMap && isset($this->valueMap[$digit])) {
                $decimals[] = $this->valueMap[$digit];
            } elseif (($decimal = $this->findDigit($digit)) !== false) {
                $decimals[] = $decimal;
            } else {
                throw new \InvalidArgumentException("The digit '$digit' does not exist");
            }
        }

        return $decimals;
    }

    private function findDigit($digit)
    {
        $value = array_search($digit, $this->numbers);

        if ($value === false && !$this->caseSensitive && is_string($digit)) {
            $find = strtolower($digit);

            foreach ($this->numbers as $key => $cmp) {
                if (is_string($cmp) && strtolower($cmp) == $find) {
                    $value = $key;
                    break;
                }
            }
        }

        return $value;
    }

    /**
     * Returns the digit representing the given decimal value.
     * @param integer $decimal Decimal value to lookup
     * @return string The digit that represents the given decimal value
     * @throws \InvalidArgumentException If the decimal value is not within the number system
     */
    public function getDigit($decimal)
    {
        return $this->getDigits([$decimal])[0];
    }

    public function getDigits(array $decimals)
    {
        $digits = [];

        foreach ($decimals as $decimal) {
            if (!isset($this->numbers[$decimal])) {
                throw new \InvalidArgumentException("The decimal value '$decimal' does not exist");
            }

            $digits[] = $this->numbers[$decimal];
        }

        return $digits;
    }

    /**
     * Finds the largest integer root shared by the radix of both number bases.
     * @param NumberBase $base Number base to compare against
     * @return integer|false Highest common integer root or false if none
     */
    public function findCommonRadixRoot (NumberBase $base)
    {
        $common = array_intersect($this->getRadixRoots(), $base->getRadixRoots());
        return count($common) > 0 ? max($common) : false;
    }

    /**
     * Returns all integer roots for the radix.
     * @return array Array of integer roots for the radix
     */
    private function getRadixRoots ()
    {
        $roots = [$this->radix];

        for ($root = 2; $root * $root <= $this->radix; $root++) {
            if ($this->isNthRootFor($this->radix, $root)) {
                $roots[] = $root;
            }
        }

        return $roots;
    }

    /**
     * Tests if given number is nth root for the number.
     * @param integer $number Number to test against
     * @param integer $root Root to test
     * @return boolean True if the number is nth root and false if not
     */
    private function isNthRootFor ($number, $root)
    {
        for ($pow = 2; pow($root, $pow) < $number; $pow++);
        return pow($root, $pow) == $number;
    }
}
