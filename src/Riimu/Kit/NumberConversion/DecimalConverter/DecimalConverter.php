<?php

namespace Riimu\Kit\NumberConversion\DecimalConverter;

use Riimu\Kit\NumberConversion\ConversionMethod\ConversionMethod;
use Riimu\Kit\NumberConversion\ConversionMethod\ConversionException;
use Riimu\Kit\NumberConversion\NumberBase;

/**
     * Converts number from base to another using arbitrary precision math.
     *
     * Decimal conversion takes advantage of arbitrary precision libraries
     * to first convert the source number into decimal and then converting that
     * number into the target base. The speed of this method depends entirely
     * on the integer library used. It is worth noting that the GMP library is
     * few magnitudes faster than BCMath, which is several magnitudes faster
     * than Internal implementation. Comparably, using GMP library, this method
     * is only few times slower than replace conversion.
     *
     * @param array $number Number to covert with most significant digit last
     * @param boolean $fractions True if converting fractions, false if not
     * @return array The converted number with most significant digit last
     */
/**
 * Decimal converter converts numbers from radix to another using decimal logic.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class DecimalConverter extends ConversionMethod
{
    /**
     * Default precision used in fraction conversion.
     * @var integer
     */
    private $precision;

    /**
     * Creates new instance of the decimal converter.
     */
    public function __construct(NumberBase $sourceBase, NumberBase $targetBase)
    {
        parent::__construct($sourceBase, $targetBase);

        $this->precision = -1;
    }

    /**
     * Sets the default precision used in fraction conversion.
     * @param integer $precision Default precision used in fraction conversion
     */
    public function setPrecision($precision)
    {
        $this->precision = (int) $precision;
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
    public function convertNumber(array $number)
    {
        if (!$this->isSupported()) {
            throw new ConversionException("This decimal converter is not supported");
        }

        $source = $this->init($this->source->getRadix());
        $target = $this->init($this->target->getRadix());

        return $this->getDigits($this->toBase($this->toDecimal(
            $this->getDecimals($number), $source), $target));
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
     * @param type $precision Precision of the resulting number or false for default
     * @return array List of digit values for the converted number
     */
    public function convertFractions(array $number)
    {
        if (!$this->isSupported()) {
            throw new ConversionException("This decimal converter is not supported");
        }

        $precision = $this->precision;
        $source = $this->init($this->source->getRadix());
        $target = $this->init($this->target->getRadix());
        $dividend = $this->toDecimal($this->getDecimals($number), $source);
        $divisor = $this->toDecimal([1] + array_fill(1, count($number), 0), $source);
        $digits = $precision > 0 ? $precision
            : $this->countDigits(count($number), $source, $target) + abs($precision);
        $zero = $this->init('0');

        for ($i = 0; $i <= $digits && $this->cmp($dividend, $zero) > 0; $i++) {
            list($digit, $dividend) = $this->div($this->mul($dividend, $target), $divisor);
            $result[] = (int) $this->val($digit);
        }

        if ($i > $digits) {
            $result = $this->round($result, $this->target->getRadix());
        }

        return $this->getDigits($result);
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
            $decimal = $this->add($decimal, $this->mul($this->init($value),
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

    abstract public function isSupported();

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
     * Performs integer division and returns quotient and remainder.
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
