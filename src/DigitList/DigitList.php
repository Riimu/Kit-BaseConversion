<?php

namespace Riimu\Kit\BaseConversion\DigitList;

/**
 * Interface for different kinds of digit lists.
 *
 * A digit list determines the different digits in numeral system and how to
 * determine their value efficiently. A digit list also knows if the digits are
 * case sensitive and if the numbers using these digits can be represented
 * using a string.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface DigitList extends \Countable
{
    /**
     * Tells if there is conflict that prevents numbers from being represented as a string.
     * @return bool True if a number cannot be represented using a string, false if it can
     */
    public function hasStringConflict();

    /**
     * Tells if the digits are case sensitive or not.
     * @return bool True if the digits are case sensitive, false if not
     */
    public function isCaseSensitive();

    /**
     * Returns all the digits in the list.
     * @return array List of digits in the list according to their value
     */
    public function getDigits();

    /**
     * Returns the digit that represents the given value.
     * @param int $value The value of the digit
     * @return mixed The digit that represents the value
     * @throws \InvalidArgumentException If the value is invalid
     */
    public function getDigit($value);

    /**
     * Returns the value for the given digit.
     * @param mixed $digit Digit to search for
     * @return int The value of the digit
     * @throws InvalidDigitException if the digit is invalid
     */
    public function getValue($digit);

    /**
     * Returns the number of different digits.
     * @return int the number of different digits
     */
    public function count();
}
