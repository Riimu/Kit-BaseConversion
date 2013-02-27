<?php

namespace Rose\NumberConversion;

/**
 * Representation for a number base.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 */
class NumberBase
{
    /**
     * The amount of numbers in the number system..
     * @var integer
     */
    private $radix;

    /**
     * List of different number in the number system.
     * @var array
     */
    private $numbers;

    /**
     * List of numbers used when the base is provided as a number.
     * @var string
     */
    private static $integerBase = 
        '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    
    private static $integerBase64 =
        'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

    /**
     * Creates a new number base.
     *
     * The number base may be provided as an array, string or an integer. If an
     * integer is provided, the that many characters is taken from the 0..9A..Z
     * character sequence. If a string is provided, then the first character
     * must represent 0 and the last character is the highest digit in the
     * number system. When an array is provided, the index tells the decimal
     * value of the character.
     *
     * @param integer|string|array $numberBase The number base to use
     * @throws \InvalidArgumentException If the given number base is invalid
     */
    public function __construct ($numberBase)
    {
        if (is_int($numberBase)) {
            $this->setBaseInteger($numberBase);
        } elseif (is_string($numberBase)) {
            $this->setBaseString($numberBase);
        } elseif (is_array($numberBase)) {
            $this->setBaseArray($numberBase);
        } else {
            throw new \InvalidArgumentException('Unexpected Number Base Type');
        }
    }

    /**
     * Sets the characters for the number system by giving the radix.
     * @param integer $integer Radix for the number base
     * @throws \InvalidArgumentException If the radix is too small or large
     */
    public function setBaseInteger ($integer)
    {
        $integer = (int) $integer;

        if ($integer < 2) {
            throw new \InvalidArgumentException('Radix must be bigger than 2');
        }
        
        $numbers = array();
        
        if ($integer <= strlen(self::$integerBase)) {
            $numbers = str_split(substr(self::$integerBase, 0, $integer));
        } elseif ($integer == 64) {
            $numbers = str_split(self::$integerBase64);
        } elseif ($integer <= 256) {
            for ($i = 0; $i < $integer; $i++) {
                $numbers[] = chr($i);
            }
        } else {
            for ($i = 0; $i < $integer; $i++) {
                $numbers[] = "#$i;";
            }
        }
        
        $this->setBaseArray($numbers);
    }

    /**
     * Sets the characters for the number system as a string.
     * @param string $string Characters for the number system
     */
    public function setBaseString ($string)
    {
        $this->setBaseArray(str_split($string));
    }

    /**
     * Sets the characters for the number system as an array.
     * @param array $array Characters for the number system
     * @throws \InvalidArgumentException If the array is not a valid number system
     */
    public function setBaseArray (array $array)
    {
        if (count($array) < 2) {
            throw new \InvalidArgumentException('Number base must have at least 2 numbers');
        }
        
        $numbers = array();
        
        // array_unique does string comparison which is not always desirable
        foreach ($array as $key => $value) {
            if (array_search($value, $numbers) !== false) {
                throw new \InvalidArgumentException('Duplicate numbers in number base');
            }
            $numbers[$key] = $value;
        }
        
        $keys = array_keys($numbers);
        sort($keys);
        
        // Sorted array from 0 to n should have identical keys and values
        if (array_flip($keys) !== $keys) {
            throw new \InvalidArgumentException('Invalid number values in number base');
        }

        $this->radix = count($numbers);
        $this->numbers = $numbers;
    }

    /**
     * Returns the number of different characters in the number system.
     * @return integer The number of different characters in the number system.
     */
    public function getRadix ()
    {
        return $this->radix;
    }

    /**
     * Returns the decimal value represented by the given character.
     * @param string $character Character to use for lookup
     * @return integer The decimal value for the provided character
     * @throws \InvalidArgumentException If the character does not exist
     */
    public function getDecimalValue ($number)
    {
        $key = array_search($number, $this->numbers);
        
        if ($key === false) {
            throw new \InvalidArgumentException('Number does not exist in the number base');
        }
        
        return $key;
    }

    /**
     * Returns the character for the given decimal value.
     * @param integer $decimal Decimal value to lookup
     * @return string The character that represents the given decimal value
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
     * Two number bases are exponential when a^n=b, a is the radix of smaller
     * number base, b is the radix of larger number base and n is an integer
     * equal or greater than 1. When two number bases are exponential, then
     * any number in the larger number base can be represented exactly by n
     * numbers of the smaller base.
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
     * Finds integer roots that are commont to radix of both number bases.
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
        $roots = array($this->radix);
        
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
     * Generates a conversion array table between two exponential number bases.
     *
     * Each key value pair in the returned array tells which series of number
     * characters to replace with characters from the target base.
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
        $minNumbers = array();

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
        
        $table = array($minNumbers, array_chunk($max->numbers, 1));
        return $min === $this ? $table : array_reverse($table);
    }
}
