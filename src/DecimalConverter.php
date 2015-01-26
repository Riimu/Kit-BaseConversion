<?php

namespace Riimu\Kit\BaseConversion;

/**
 * Converts numbers using GMP extension.
 *
 * DecimalConverter employs arbitrary-precision integer arithmetic to convert
 * digits to decimal system and then convert them to the target base. Due to
 * speed of GMP, even fractions are calculated using an integer based
 * algorithm to determine the digits.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DecimalConverter implements Converter
{
    /**
     * Precision for fraction conversions.
     * @var integer
     */
    private $precision;

    /**
     * Number base for provided numbers.
     * @var NumberBase
     */
    private $source;

    /**
     * Number base for returned numbers.
     * @var NumberBase
     */
    private $target;

    /**
     * Creates new instance of the DecimalConverter.
     * @param NumberBase $source Number base for provided numbers.
     * @param NumberBase $target Number base for returned numbers.
     */
    public function __construct(NumberBase $source, NumberBase $target)
    {
        $this->precision = -1;
        $this->source = $source;
        $this->target = $target;
    }

    public function setPrecision($precision)
    {
        $this->precision = (int) $precision;
    }

    public function convertInteger(array $number)
    {
        return $this->target->getDigits($this->toBase($this->toDecimal($this->source->getValues($number))));
    }

    public function convertFractions(array $number)
    {
        $target = gmp_init($this->target->getRadix());
        $dividend = $this->toDecimal($this->source->getValues($number));
        $divisor = $this->toDecimal([1] + array_fill(1, max(count($number), 1), 0));
        $digits = $this->getFractionDigitCount(count($number));
        $zero = gmp_init('0');
        $result = [];

        for ($i = 0; $i < $digits && gmp_cmp($dividend, $zero) > 0; $i++) {
            list($digit, $dividend) = gmp_div_qr(gmp_mul($dividend, $target), $divisor);
            $result[] = gmp_intval($digit);
        }

        return $this->target->getDigits(empty($result) ? [0] : $result);
    }

    /**
     * Converts the number from source base to gmp resource.
     * @param array $number List of digit values with least significant first
     * @return resource resulting number as a gmp resource
     */
    private function toDecimal(array $number)
    {
        if ($this->source->getRadix() === 10) {
            return gmp_init(implode('', $number));
        }

        $decimal = gmp_init('0');
        $count = count($number);
        $radix = gmp_init($this->source->getRadix());

        for ($i = 0; $i < $count; $i++) {
            $decimal = gmp_add($decimal, gmp_mul(gmp_init($number[$i]), gmp_pow($radix, $count - $i - 1)));
        }

        return $decimal;
    }

    /**
     * Converts GMP resource to target base.
     * @param resource $decimal Number as GMP resource
     * @return array List of digit values for the converted number
     */
    private function toBase($decimal)
    {
        if ($this->target->getRadix() === 10) {
            return array_map('intval', str_split(gmp_strval($decimal)));
        }

        $zero = gmp_init('0');
        $radix = gmp_init($this->target->getRadix());
        $result = [];

        while (gmp_cmp($decimal, $zero) > 0) {
            list($decimal, $modulo) = gmp_div_qr($decimal, $radix);
            $result[] = gmp_intval($modulo);
        }

        return empty($result) ? [0] : array_reverse($result);
    }

    /**
     * Counts the number of digits required in target base.
     * @param integer $count Number of digits in the original number
     * @return integer Number of digits required in the target base
     */
    private function getFractionDigitCount($count)
    {
        if ($this->precision > 0) {
            return $this->precision;
        }

        $target = gmp_init($this->target->getRadix());
        $maxFraction = gmp_pow(gmp_init($this->source->getRadix()), $count);
        $digits = 1;

        while (gmp_cmp(gmp_pow($target, $digits), $maxFraction) < 0) {
            $digits++;
        }

        return $digits + abs($this->precision);
    }
}
