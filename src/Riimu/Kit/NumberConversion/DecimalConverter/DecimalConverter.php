<?php

namespace Riimu\Kit\NumberConversion\DecimalConverter;

/**
 * Decimal converter converts numbers from radix to another using decimal logic.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class DecimalConverter
{
    /**
     * Default precision used in fraction conversion.
     * @var integer
     */
    private $defaultPrecision;

    /**
     * Creates new instance of the decimal converter.
     */
    public function __construct()
    {
        $this->defaultPrecision = -1;
    }

    /**
     * Sets the default precision used in fraction conversion.
     * @param integer $precision Default precision used in fraction conversion
     */
    public function setDefaultPrecision($precision)
    {
        $this->defaultPrecision = (int) $precision;
    }

    /**
     * Converts number from radix to another using decimal logic.
     *
     * When called, the method is given the original number as an array, where
     * each value represents the decimal value of the digit in that position.
     * The least significant digit is in the first index. For example, a HEX
     * value of 'A09FF' would be given as [10, 0, 9, 15, 15]. The method will
     * then convert the number from the source base indicated by the source
     * radix to the target base indicated by the target radix and then return
     * it as an array similar to the input array. For example, the
     * aforementioned number coverted from radix 16 to radix 8 would be
     * returned as [2, 4, 0, 4, 7, 7, 7].
     *
     * @param array $number List of digit values with least significant first
     * @param integer $sourceRadix Radix of the source base
     * @param integer $targetRadix Radix of the target base
     * @return array List of digit values for the converted number
     */
    public function convertNumber(array $number, $sourceRadix, $targetRadix)
    {
        $source = $this->init($sourceRadix);
        $target = $this->init($targetRadix);

        return $this->toBase($this->toDecimal($number, $source), $target);
    }

    /**
     * Converts fractional part of the number from base to another.
     *
     * Problem with converting fractions is that you cannot always convert them
     * accurately. The precision argument can be used to determine how
     * accurately you want the result. Positive number indicates the number
     * of digits you want. If you use 0, then the number of digits in the
     * result is the smallest number of digits that can be used to represent
     * number more accurate than the source number. For example, to represent
     * 0.1 in base 2, you need at least 4 digits, because with 3 digits you
     * only get 1/8 accuracy and it requires at least 1/10 accuracy. Using
     * negative value will add the absolute value of the argument to this
     * estimated digit count.
     *
     * The last digit is always rounded, unless the rounding would cause an
     * overflow. Extranous zeroes from the end will not be trimmed, unless the
     * number can be accurately converted to smaller amount of digits than
     * indicated by the precision.
     *
     * @param array $number List of digit values with least significant first
     * @param integer $sourceRadix Radix of the source base
     * @param integer $targetRadix Radix of the target base
     * @param type $precision Precision of the resulting number
     * @return array List of digit values for the converted number
     */
    public function convertFractions(array $number, $sourceRadix, $targetRadix, $precision = false)
    {
        if ($precision === false) {
            $precision = $this->defaultPrecision;
        }

        $source = $this->init($sourceRadix);
        $target = $this->init($targetRadix);
        $dividend = $this->toDecimal($number, $source);
        $divisor = $this->toDecimal([1] + array_fill(1, count($number), 0), $source);
        $digits = $precision > 0 ? $precision
            : $this->countDigits(count($number), $source, $target) + abs($precision);
        $zero = $this->init('0');

        for ($i = 0; $i <= $digits && $this->cmp($dividend, $zero) > 0; $i++) {
            list($digit, $dividend) = $this->div($this->mul($dividend, $target), $divisor);
            $result[] = (int) $this->val($digit);
        }

        return $i > $digits ? $this->round($result, $targetRadix) : $result;
    }

    /**
     * Converts the number from given radix to decimal resource.
     * @param array $number List of digit values with least significant first
     * @param resource $radix Radix of the given number as number resource
     * @return resource resulting number as number resource
     */
    private function toDecimal(array $number, $radix)
    {
        if ($this->val($radix) === '10') {
            return $this->init(implode('', $number));
        }

        $decimal = $this->init('0');
        $power = 0;

        foreach (array_reverse($number) as $value) {
            $decimal = $this->add($decimal, $this->mul($value,
                $this->pow($radix, $power++)));
        }

        return $decimal;
    }

    /**
     * Converts decimal from number resource to target radix.
     * @param resource $decimal Decimal number as number resource
     * @param resource $radix Target radix as number resource
     * @return array List of digit values for the converted number
     */
    private function toBase($decimal, $radix)
    {
        if ($this->val($radix) === '10') {
            return array_map('intval', str_split($this->val($decimal)));
        }

        $zero = $this->init('0');

        while ($this->cmp($decimal, $zero) > 0) {
            list($decimal, $modulo) = $this->div($decimal, $radix);
            $result[] = (int) $this->val($modulo);
        }

        return empty($result) ? [0] : array_reverse($result);
    }

    /**
     * Counts the number of digits require in the target radix.
     * @param integer $count Number of digits in the original number
     * @param resource $source Source radix as number resource
     * @param resource $target Target radix as number resource
     * @return integer Number of digits required in the target base
     */
    private function countDigits($count, $source, $target)
    {
        $maxFraction = $this->pow($source, $count);
        $targetFraction = $target;

        for ($pow = 1; $this->cmp($targetFraction, $maxFraction) <= 0; $pow++) {
            $targetFraction = $this->mul($targetFraction, $target);
        }

        return $pow;
    }

    /**
     * Rounds the number by removing the last digit.
     * @param array $number List of digit values with least significant first
     * @param integer $radix Target radix
     * @return array List of digit values in the rounded number
     */
    private function round(array $number, $radix)
    {
        if (array_pop($number) >= $radix / 2) {
            $i = count($number) - 1;
            for ($number[$i] += 1; $number[$i] == $radix; $i--) {
                $number[$i] = 0;

                // If it overflows, don't round it
                if ($i === 0) {
                    return array_fill(0, count($number), $radix - 1);
                }

                $number[$i - 1] += 1;
            }
        }

        return $number;
    }

    /**
     * Initializes the number resource from given number.
     * @param integer|string $number Number to initialize as a number resource
     * @return resource The provided number as a number resource
     */
    abstract protected function init($number);

    /**
     * Converts the number resource into a string.
     * @param resource $number The number given as an integer
     * @return string The number converted into a string
     */
    abstract protected function val($number);

    /**
     * Adds two integers together.
     * @param resource $a The left operand given as an integer
     * @param resource $b The right operand given as an integer
     * @return resource The resulting sum returned as an integer
     */
    abstract protected function add($a, $b);

    /**
     * Multiplies to integers together.
     * @param resource $a The left operand given as an integer
     * @param resource $b The right operand given as an integer
     * @return resource The resulting product returned as an integer
     */
    abstract protected function mul($a, $b);

    /**
     * Performs exponentiation on the two integers.
     * @param resource $a The number base given as an integer
     * @param integer $b The exponent given as an integer
     * @return resource The result given as an integer
     */
    abstract protected function pow($a, $b);

    /**
     * Performs integer division and returns quotient and remained.
     * @param resource $a The dividend given as an integer
     * @param resource $b The divisor given as an integer
     * @return array First index with the quotient and second as the remainder
     */
    abstract protected function div($a, $b);

    /**
     * Compares the two integers.
     * @param resource $a The left operand as an integer
     * @param resource $b The right operand as an integer
     * @return integer >0, if $a > $b, 0 if $a == $b and <0 if $a < $b
     */
    abstract protected function cmp($a, $b);
}
