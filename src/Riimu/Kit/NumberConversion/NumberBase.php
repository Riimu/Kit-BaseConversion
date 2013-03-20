<?php

namespace Riimu\Kit\NumberConversion;

/**
 * Represents a number base.
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
        } elseif ($integer == 64) {
            $this->numbers = str_split(self::$integerBase64);
        } elseif ($integer <= 256) {
            for ($i = 0; $i < $integer; $i++) {
                $this->numbers[] = chr($i);
            }
        } else {
            for ($i = 0; $i < $integer; $i++) {
                $this->numbers[] = "#$i;";
            }
        }

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
        $this->radix = count($this->numbers);
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

        // array_unique does string comparison which is not always desirable
        foreach ($array as $key => $value) {
            if (array_search($value, $numbers) !== false) {
                throw new \InvalidArgumentException('Duplicate values in number base');
            }
            $numbers[$key] = $value;
        }

        $keys = array_keys($numbers);
        sort($keys);

        // Sorted array from 0 to n should have identical keys and values
        if (array_flip($keys) !== $keys) {
            throw new \InvalidArgumentException('Invalid indexes in the number base');
        }

        $this->radix = count($numbers);
        $this->numbers = $numbers;
    }

    /**
     * Returns the radix (i.e. size) of the number system.
     * @return integer Radix of the number base
     */
    public function getRadix ()
    {
        return $this->radix;
    }

    /**
     * Returns the decimal value represented by the given digit.
     * @param string $digit The digit to look up
     * @return integer The decimal value for the provided digit
     * @throws \InvalidArgumentException If the given digit does not exist
     */
    public function getDecimalValue ($digit)
    {
        $key = array_search($digit, $this->numbers);

        if ($key === false) {
            throw new \InvalidArgumentException('The number does not exist in the number base');
        }

        return $key;
    }

    /**
     * Returns the value representing the given decimal value.
     * @param integer $decimal Decimal value to lookup
     * @return string The value that represents the given decimal value
     * @throws \InvalidArgumentException If the decimal value is not within the number system
     */
    public function getFromDecimalValue ($decimal)
    {
        if (!isset($this->numbers[$decimal])) {
            throw new \InvalidArgumentException('Decimal value does not exist in the number base');
        }

        return $this->numbers[$decimal];
    }

    /**
     * Tells if the number base is exponential with another number base.
     *
     * Two number bases are exponential if the radix of either base is the nth
     * root of the other base. When two number bases are exponential, then
     * any digit in the larger number base can be represented exactly by n
     * digits of the smaller number base.
     *
     * @param NumberBase $base Number base to test against
     * @return boolean True if the number bases are exponential, false if not
     */
    public function isExponentialBase (NumberBase $base)
    {
        return $this->radix == $base->radix || ($this->radix > $base->radix
            ? $this->isNthRootFor($this->radix, $base->radix)
            : $this->isNthRootFor($base->radix, $this->radix));
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

    /**
     * Generates a conversion table between two exponential number bases.
     *
     * The returned value contains two arrays. The first array contains digits
     * in the source base and the second array contains the corresponding digits
     * in the target base. Replacement can be performed by replacing the
     * sequence of digits from the first array with the sequence of digits in
     * the second array with the same index value. For example, the conversion
     * table returned between base 2 and 4 would be:
     *
     * <code>[
     *   [[0, 0], [0, 1], [1, 0], [1, 1]],
     *   [[0], [1], [2], [3]]
     * ]</code>
     *
     *
     * @param NumberBase $target The target for the conversion
     * @return array Array containing the conversions
     * @throws \InvalidArgumentException If the number bases are not exponential
     */
    public function createConversionTable (NumberBase $target)
    {
        if (!$this->isExponentialBase($target)) {
            throw new \InvalidArgumentException(
                'Cannot create conversion table from non exponential number bases');
        }

        if ($this->radix > $target->radix) {
            $min = $target;
            $max = $this;
        } else {
            $min = $this;
            $max = $target;
        }

        $last = $min->numbers[$min->radix - 1];
        $size = (int) log($max->radix, $min->radix);
        $number = array_fill(0, $size, $min->numbers[0]);
        $next = array_fill(0, $size, 0);
        $minNumbers = [];

        for ($i = 0; $i < $max->radix; $i++) {
            if ($i > 0) {
                for ($j = $size - 1; $number[$j] == $last; $j--) {
                    $number[$j] = $min->numbers[0];
                    $next[$j] = 0;
                }
                $number[$j] = $min->numbers[++$next[$j]];
            }
            $minNumbers[] = $number;
        }

        $table = [$minNumbers, array_chunk($max->numbers, 1)];
        return $min === $this ? $table : array_reverse($table);
    }
}
