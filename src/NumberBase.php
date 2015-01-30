<?php

namespace Riimu\Kit\BaseConversion;

/**
 * Represents a positional numeral system with a specific number base.
 *
 * NumberBase provides convenience when dealing numbers that are represented by
 * a specific list of digits. NumberBase can interpret numbers presented as
 * strings and also provides convenience when creating lists of digits.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class NumberBase
{
    /** @var DigitList\DigitList List of digits */
    private $digits;

    /** @var string|integer|false Pattern for splitting strings into digits */
    private $digitPattern;

    /**
     * Creates a new instance of NumberBase.
     *
     * The constructor takes a list of digits for the numeral system as the
     * constructor parameter. This can either be an instance of DigitList or
     * it can be a string, an integer or an array that is used to construct a
     * the appropriate type of DigitList. See the constructors for appropriate
     * classes for how to define those digit lists.
     *
     * @param DigitList\DigitList|integer|string|array $digitList List of digits
     * @throws \InvalidArgumentException If the list of digits is invalid
     */
    public function __construct($digitList)
    {
        $this->digits = $digitList instanceof DigitList\DigitList
            ? $digitList : $this->buildDigitList($digitList);
    }

    /**
     * Returns an appropriate type of digit list based on the parameter.
     * @param integer|string|array $digitList List of digits
     * @return DigitList\DigitList Appropriate type of digit list
     */
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
     * Tells if numbers using this numeral system cannot be represented using a string.
     * @return boolean True if string representation is not supported, false if it is
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
        } catch (DigitList\InvalidDigitException $ex) {
            return false;
        }

        return true;
    }

    /**
     * Returns the decimal value represented by the given digit.
     * @param mixed $digit The digit to look up
     * @return integer The decimal value for the provided digit
     * @throws InvalidDigitException If the given digit is invalid
     */
    public function getValue($digit)
    {
        return $this->digits->getValue($digit);
    }

    /**
     * Returns the decimal values for given digits.
     * @param array $digits Array of digits to look up
     * @return integer[] Array of digit values
     * @throws InvalidDigitException If any of the digits is invalid
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
     * @param integer[] $decimals Decimal values to look up
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
     * Replaces all values in the array with actual digits from the digit list.
     *
     * This method takes a list of digits and returns the digits properly
     * capitalized and typed. This can be used to canonize numbers when dealing
     * with case insensitive and loosely typed number bases.
     *
     * @param array $digits List of digits to canonize
     * @return array Canonized list of digits
     * @throws InvalidDigitException If any of the digits are invalid
     */
    public function canonizeDigits(array $digits)
    {
        $result = $this->getDigits($this->getValues($digits));
        return empty($result) ? [$this->digits->getDigit(0)] : $result;
    }

    /**
     * Splits number string into individual digits.
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
     * Creates and returns the pattern for splitting strings into digits.
     * @return string|integer Pattern to split strings into digits
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
