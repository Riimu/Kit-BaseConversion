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
    /**
     * The number of unique digits in the numeral system.
     * @var integer
     */
    private $radix;

    /**
     * Lists different digits in the numeral system by their values.
     * @var array
     */
    private $digits;

    /**
     * Maps digits to their values, when possible.
     * @var array
     */
    private $valueMap;

    /**
     * Tells if the numeral system is case sensitive or not.
     * @var boolean
     */
    private $caseSensitive;

    /**
     * Tells if the numeral system supports string numbers.
     * @var boolean
     */
    private $stringConflict;

    /**
     * Tells how to split strings according to this numeral system.
     * @var false|integer|string
     */
    private $splitter;

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
     * @param integer|string|array $numberBase The numeral system to use
     * @throws \InvalidArgumentException If the given numeral system is invalid
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
     * Sets the digits according to the given number base.
     * @param integer $integer Radix for the numeral system
     * @throws \InvalidArgumentException If the radix is too small
     */
    private function setBaseInteger ($integer)
    {
        $integer = (int) $integer;

        if ($integer < 2) {
            throw new \InvalidArgumentException('Radix must be bigger than 2');
        }

        $this->digits = [];

        if ($integer <= strlen(self::$integerBase)) {
            $this->digits = str_split(substr(self::$integerBase, 0, $integer));
        } elseif ($integer === 64) {
            $this->digits = str_split(self::$integerBase64);
        } elseif ($integer <= 256) {
            for ($i = 0; $i < $integer; $i++) {
                $this->digits[] = chr($i);
            }
        } else {
            $format = '#%0' . strlen($integer - 1) . 'd';
            for ($i = 0; $i < $integer; $i++) {
                $this->digits[] = sprintf($format, $i);
            }
        }

        $this->radix = count($this->digits);
        $this->valueMap = array_flip($this->digits);
        $this->caseSensitive = count(array_change_key_case($this->valueMap)) !== $this->radix;
        $this->stringConflict = false;
    }

    /**
     * Uses the characters in the given string as digits in the numeral system.
     * @param string $string Digits for the numeral system
     * @throws \InvalidArgumentException If there are too few or duplicate characters
     */
    private function setBaseString ($string)
    {
        if (strlen($string) < 2) {
            throw new \InvalidArgumentException('Number base needs at least 2 characters');
        } elseif (strlen(count_chars($string, 3)) !== strlen($string)) {
            throw new \InvalidArgumentException('Duplicate characters in the number base');
        }

        $this->radix = strlen($string);
        $this->digits = str_split($string);
        $this->valueMap = array_flip($this->digits);
        $this->caseSensitive = count(array_change_key_case($this->valueMap)) !== $this->radix;
        $this->stringConflict = false;
    }

    /**
     * Uses the values in the array to represent the digits.
     * @param array $array Digits for the numeral system.
     * @throws \InvalidArgumentException If too few or duplicate values exist or indexes are not valid
     */
    private function setBaseArray (array $array)
    {
        if (count($array) < 2) {
            throw new \InvalidArgumentException('Number base must have at least 2 digits');
        }

        $numbers = [];
        $strings = [];
        $mapped = true;

        foreach ($array as $key => $value) {
            if (array_search($value, $numbers) !== false) {
                throw new \InvalidArgumentException('Duplicate digits in the number base');
            }

            $numbers[$key] = $value;

            if (is_string($value)) {
                $strings[] = strtolower($value);
            } elseif (!is_int($value)) {
                $mapped = false;
            }
        }

        if (array_diff_key($numbers, range(0, count($numbers) - 1)) !== []) {
            throw new \InvalidArgumentException('Invalid digit values in the number base');
        }

        $this->radix = count($numbers);
        $this->digits = $numbers;
        $this->valueMap = $mapped ? array_flip($numbers) : null;
        $this->caseSensitive = count($strings) != count(array_flip($strings));

        if ($this->valueMap) {
            $this->stringConflict = false;
            $stringDigits = array_map('strval', $this->digits);
            foreach ($stringDigits as $a => $needle) {
                foreach ($stringDigits as $b => $haystack) {
                    if ($a !== $b && strpos($haystack, $needle) !== false) {
                        $this->stringConflict = true;
                        break 2;
                    } elseif ($a !== $b && stripos($haystack, $needle) !== false) {
                        $this->caseSensitive = true;
                    }
                }
            }
        } else {
            $this->stringConflict = true;
        }
    }

    /**
     * Tells if the number using this numeral system can be represented as string.
     * @return boolean True if possible, false if not
     */
    public function hasStringConflict()
    {
        return $this->stringConflict;
    }

    /**
     * Tells if this numeral system is case sensitive or not
     * @return boolean True if case sensitive, false if not
     */
    public function isCaseSensitive()
    {
        return $this->caseSensitive;
    }

    /**
     * Returns the radix (i.e. base) of the numeral system.
     * @return integer Radix of the numeral system
     */
    public function getRadix ()
    {
        return $this->radix;
    }

    /**
     * Returns list of all digits in the numeral system.
     * @return array Array of digits in the numeral system
     */
    public function getDigitList()
    {
        return $this->digits;
    }

    /**
     * Tells if the given digit is part of this numeral system.
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
    public function getValue($digit)
    {
        return $this->getValues([$digit])[0];
    }

    /**
     * Returns the decimal values for given digits.
     * @param array $digits Array of digits to look up
     * @return array Array of digit values
     * @throws \InvalidArgumentException If some digit does not exist
     */
    public function getValues(array $digits)
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

    /**
     * Return the value for the digit in the numeral system.
     * @param mixed $digit Digit to look up
     * @return integer|boolean Decimal value for the digit or false if not found
     */
    private function findDigit($digit)
    {
        $value = array_search($digit, $this->digits);

        if ($value === false && !$this->caseSensitive && is_string($digit)) {
            foreach ($this->digits as $key => $cmp) {
                if (is_string($cmp) && strcasecmp($cmp, $digit) === 0) {
                    return $key;
                }
            }
        }

        return $value;
    }

    /**
     * Returns the digit representing the given decimal value.
     * @param integer $decimal Decimal value to lookup
     * @return mixed The digit that represents the given decimal value
     * @throws \InvalidArgumentException If the decimal value is not within the number system
     */
    public function getDigit($decimal)
    {
        return $this->getDigits([$decimal])[0];
    }

    /**
     * Returns the digits representing the given decimal values.
     * @param array $decimals Decimal values to look up
     * @return array Array of digits that represent the given decimal values
     * @throws \InvalidArgumentException If any of the decimal values is invalid
     */
    public function getDigits(array $decimals)
    {
        $digits = [];

        foreach ($decimals as $decimal) {
            if (!isset($this->digits[$decimal])) {
                throw new \InvalidArgumentException("The decimal value '$decimal' does not exist");
            }

            $digits[] = $this->digits[$decimal];
        }

        return $digits;
    }

    /**
     * Finds the largest integer root shared by the radix of both numeral systems.
     * @param NumberBase $base Numeral system to compare against
     * @return integer|false Highest common integer root or false if none
     */
    public function findCommonRadixRoot (NumberBase $base)
    {
        $common = array_intersect($this->getRadixRoots(), $base->getRadixRoots());
        return count($common) > 0 ? max($common) : false;
    }

    /**
     * Returns all integer roots for the radix.
     * @return integer[] Array of integer roots for the radix
     */
    private function getRadixRoots ()
    {
        $roots = [$this->radix];

        for ($i = 2; ($root = (int) pow($this->radix, 1 / $i)) > 1; $i++) {
            if (pow($root, $i) === $this->radix) {
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
        return empty($result) ? [$this->digits[0]] : $result;
    }

    /**
     * Splits number string into digits.
     * @param string $string String to split into array of digits
     * @return array Array of digits
     * @throws \RuntimeException If numeral system does not support strings
     */
    public function splitString($string)
    {
        if (!isset($this->splitter)) {
            $this->splitter = $this->createSplitter();
        }

        if ($this->splitter === false) {
            throw new \RuntimeException('Strings are not supported');
        } elseif ((string) $string === '') {
            $digits = [];
        } elseif (is_int($this->splitter)) {
            $digits = str_split($string, $this->splitter);
        } else {
            preg_match_all($this->splitter, $string, $match);
            $digits = $match[0];
        }

        return $this->canonizeDigits($digits);
    }

    /**
     * Determines the rule on how to split number strings.
     * @return false|integer|string Splitting rule for strings
     */
    private function createSplitter()
    {
        if ($this->stringConflict) {
            return false;
        }

        $lengths = array_map('strlen', $this->digits);

        if (count(array_flip($lengths)) === 1) {
            return array_pop($lengths);
        }

        $string = implode('|', array_map(function ($value) {
            return preg_quote((string) $value, '/');
        }, $this->digits));

        return "/$string|.+/s" . ($this->caseSensitive ? '' : 'i');
    }
}
