<?php

namespace Riimu\Kit\NumberConversion\Converter\Decimal;

use Riimu\Kit\NumberConversion\Converter\IntegerConverter;
use Riimu\Kit\NumberConversion\Converter\FractionConverter;
use Riimu\Kit\NumberConversion\Converter\AbstractConverter;
use Riimu\Kit\NumberConversion\Converter\ConversionException;

/**
 * Decimal converter converts numbers from radix to another using decimal logic.
 *
 * Decimal conversion takes advantage of arbitrary precision libraries
 * to first convert the source number into decimal and then converting that
 * number into the target base. The efficiency of this conversion method depends
 * mostly on the speed of the arbitrary precision math libraries.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class AbstractDecimalConverter extends AbstractConverter
    implements IntegerConverter, FractionConverter
{
    /**
     * Precision for fraction conversions.
     * @var integer
     */
    private $precision;

    /**
     * Creates new instance of the decimal converter.
     */
    public function __construct()
    {
        parent::__construct();
        $this->precision = -1;
    }

    public function setPrecision($precision)
    {
        $this->precision = (int) $precision;
    }

    public function convertInteger(array $number)
    {
        if (!$this->isSupported()) {
            throw new ConversionException("This decimal converter is not supported");
        }

        $source = $this->init($this->source->getRadix());
        $target = $this->init($this->target->getRadix());

        return $this->getDigits($this->toBase($this->toDecimal(
            $this->getValues($number), $source), $target));
    }

    public function convertFractions(array $number)
    {
        if (!$this->isSupported()) {
            throw new ConversionException("This decimal converter is not supported");
        }

        $precision = $this->precision;
        $source = $this->init($this->source->getRadix());
        $target = $this->init($this->target->getRadix());
        $dividend = $this->toDecimal($this->getValues($number), $source);
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

    /**
     * Tells if the PHP extensions required by this converter are available.
     * @return boolean True if available, false if not
     */
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
