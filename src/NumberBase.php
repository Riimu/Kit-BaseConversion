<?php

namespace Riimu\Kit\BaseConversion;

/**
 * Represents a positional numeral system with a specific number base.
 *
 * A positional numeral system consists of radix (the number of unique digits in
 * the numeral system) and the list of digits. NumberBase provides methods for
 * finding values for digits in the numeral system and the digits representing
 * given values.
 *
 * NumberBase can be defined in multiple different ways. For more information,
 * see the details on the constructor. NumberBase is completely agnostic to
 * the type of digits used to define the number base, but all comparison are
 * done using loose comparison operators. Thus, for example, the integer 0 and
 * the string "0" are considered to be the same digit.
 *
 * NumberBase will, however, treat any numeral system as case insensitive if
 * possible. Only if the numeral system has lower and upper case version of the
 * same character as different digits, will it get treated in case sensitive
 * manner.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class NumberBase
{
    /** @var DigitList\DigitList List of digits */
    private $digits;

    /**
     * Tells how to split strings according to this numeral system.
     * @var false|integer|string
     */
    private $digitPattern;

    /**
     * Creates a new instance of NumberBase.
     *
     * The digits in the numeral system can be provided as an array, string
     * or integer.
     *
     * If an integer is provided, then the digits in the numeral system
     * depend on the value of the integer. For bases of 62 and smaller, digits
     * are used fom series of 0-9A-Za-z. If the base is exactly 64, the
     * characters from base 64 encoding are used. For bases smaller or equal
     * to 256 the digits are represented by bytes of equal value. If the base is
     * bigger than 256, then each digit is represented by '#num' where 'num' is
     * replaced by the decimal value of the digit.
     *
     * If a string is provided, then characters in that string are used as the
     * digits and their position in the string determine their decimal value.
     * For example hexadecimal base would be given as '0123456789ABCDEF'.
     * Duplicate characters in the number base will cause an exception.
     *
     * If an array is provided, then values in the array are used as the digits
     * and their indexes as their decimal values. Any type of values may be
     * used, but duplicate values (with loose comparison) will cause an
     * exception. Any missing decimal value in the indexes will also cause an
     * exception.
     *
     * @param DigitList\DigitList|integer|string|array $digitList List of digits
     * @throws \InvalidArgumentException If the list of digits is invalid
     */
    public function __construct($digitList)
    {
        $this->digits = $digitList instanceof DigitList\DigitList
            ? $digitList : $this->buildDigitList($digitList);
    }

    private function buildDigitList($digitList)
    {
        if (is_int($digitList)) {
            return new DigitList\IntegerDigitList($digitList);
        } elseif (is_string($digitList)) {
            return new DigitList\StringDigitList($digitList);
        } elseif (is_array($digitList)) {
            return new DigitList\ArrayDigitList($digitList);
        }

        throw new \InvalidArgumentException('Unexpected number base type');
    }

    /**
     * Tells if the number using this numeral system can be represented as string.
     * @return boolean True if possible, false if not
     */
    public function hasStringConflict()
    {
        return $this->digits->hasStringConflict();
    }

    /**
     * Tells if this numeral system is case sensitive or not
     * @return boolean True if case sensitive, false if not
     */
    public function isCaseSensitive()
    {
        return $this->digits->isCaseSensitive();
    }

    /**
     * Returns the radix (i.e. base) of the numeral system.
     * @return integer Radix of the numeral system
     */
    public function getRadix()
    {
        return count($this->digits);
    }

    /**
     * Returns list of all digits in the numeral system.
     * @return array Array of digits in the numeral system
     */
    public function getDigitList()
    {
        return $this->digits->getDigits();
    }

    /**
     * Tells if the given digit is part of this numeral system.
     * @param mixed $digit The digit to look up
     * @return boolean True if the digit exists, false is not
     */
    public function hasDigit($digit)
    {
        try {
            $this->digits->getValue($digit);
        } catch (\InvalidArgumentException $ex) {
            return false;
        }

        return true;
    }

    /**
     * Returns the decimal value represented by the given digit.
     * @param mixed $digit The digit to look up
     * @return integer The decimal value for the provided digit
     * @throws \InvalidArgumentException If the given digit does not exist
     */
    public function getValue($digit)
    {
        return $this->digits->getValue($digit);
    }

    /**
     * Returns the decimal values for given digits.
     * @param array $digits Array of digits to look up
     * @return array Array of digit values
     * @throws \InvalidArgumentException If some digit does not exist
     */
    public function getValues(array $digits)
    {
        return array_map([$this->digits, 'getValue'], $digits);
    }

    /**
     * Returns the digit representing the given decimal value.
     * @param integer $decimal Decimal value to lookup
     * @return mixed The digit that represents the given decimal value
     * @throws \InvalidArgumentException If the decimal value is not within the number system
     */
    public function getDigit($decimal)
    {
        return $this->digits->getDigit($decimal);
    }

    /**
     * Returns the digits representing the given decimal values.
     * @param array $decimals Decimal values to look up
     * @return array Array of digits that represent the given decimal values
     * @throws \InvalidArgumentException If any of the decimal values is invalid
     */
    public function getDigits(array $decimals)
    {
        return array_map([$this->digits, 'getDigit'], $decimals);
    }

    /**
     * Finds the largest integer root shared by the radix of both numeral systems.
     * @param NumberBase $base Numeral system to compare against
     * @return integer|false Highest common integer root or false if none
     */
    public function findCommonRadixRoot(NumberBase $base)
    {
        $common = array_intersect($this->getRadixRoots(), $base->getRadixRoots());
        return count($common) > 0 ? max($common) : false;
    }

    /**
     * Returns all integer roots for the radix.
     * @return integer[] Array of integer roots for the radix
     */
    private function getRadixRoots()
    {
        $radix = count($this->digits);
        $roots = [$radix];

        for ($i = 2; ($root = (int) pow($radix, 1 / $i)) > 1; $i++) {
            if (pow($root, $i) === $radix) {
                $roots[] = $root;
            }
        }

        return $roots;
    }

    /**
     * Replaces digits in the list with digits of proper type in the numeral system.
     *
     * As all comparisons are done using loose comparisons, an array of digits
     * may have different representations than in the numeral system. This
     * method replaces all digits with the actual values and correct types used
     * by the numeral system.
     *
     * @param array $digits List of digits to canonize
     * @return array Canonized list of digits
     * @throws \InvalidArgumentException If any of the digits does not exist
     */
    public function canonizeDigits(array $digits)
    {
        $result = $this->getDigits($this->getValues($digits));
        return empty($result) ? [$this->digits->getDigit(0)] : $result;
    }

    /**
     * Splits number string into digits.
     * @param string $string String to split into array of digits
     * @return array Array of digits
     * @throws \RuntimeException If numeral system does not support strings
     */
    public function splitString($string)
    {
        if ($this->digits->hasStringConflict()) {
            throw new \RuntimeException('The number base does not support string presentation');
        }

        $pattern = $this->getDigitPattern();

        if ((string) $string === '') {
            $digits = [];
        } elseif (is_int($pattern)) {
            $digits = str_split($string, $this->digitPattern);
        } else {
            preg_match_all($pattern, $string, $match);
            $digits = $match[0];
        }

        return $this->canonizeDigits($digits);
    }

    /**
     * Determines the rule on how to split number strings.
     * @return false|integer|string Splitting rule for strings
     */
    private function getDigitPattern()
    {
        if (!isset($this->digitPattern)) {
            $lengths = array_map('strlen', $this->digits->getDigits());

            if (count(array_flip($lengths)) === 1) {
                $this->digitPattern = array_pop($lengths);
            } else {
                $string = implode('|', array_map('preg_quote', $this->digits->getDigits()));
                $this->digitPattern = "($string|.+)s" . ($this->digits->isCaseSensitive() ? '' : 'i');
            }
        }

        return $this->digitPattern;
    }
}
