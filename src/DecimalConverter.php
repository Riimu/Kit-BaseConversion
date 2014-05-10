<?php

namespace Riimu\Kit\NumberConversion;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DecimalConverter
{
    /**
     * Precision for fraction conversions.
     * @var integer
     */
    private $precision;

    private $source;
    private $target;

    /**
     * Creates new instance of the decimal converter.
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
        return $this->target->getDigits($this->toBase(
            $this->toDecimal($this->source->getValues($number))));
    }

    public function convertFractions(array $number)
    {
        $target = gmp_init($this->target->getRadix());
        $dividend = $this->toDecimal($this->source->getValues($number));
        $divisor = $this->toDecimal([1] + array_fill(1, max(count($number), 1), 0));
        $digits = $this->precision > 0 ? $this->precision
            : $this->countDigits(count($number)) + abs($this->precision);
        $zero = gmp_init('0');
        $result = [];

        for ($i = 0; $i < $digits && gmp_cmp($dividend, $zero) > 0; $i++) {
            list($digit, $dividend) = gmp_div_qr(gmp_mul($dividend, $target), $divisor);
            $result[] = gmp_intval($digit);
        }

        return $this->target->getDigits(empty($result) ? [0] : $result);
    }

    /**
     * Converts the number from given radix to decimal resource.
     * @param array $number List of digit values with least significant first
     * @param resource $radix Radix of the given number as number resource
     * @return resource resulting number as number resource
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
            $decimal = gmp_add($decimal, gmp_mul(gmp_init($number[$i]),
                gmp_pow($radix, $count - $i - 1)));
        }

        return $decimal;
    }

    /**
     * Converts decimal from number resource to target radix.
     * @param resource $decimal Decimal number as number resource
     * @param resource $radix Target radix as number resource
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
     * Counts the number of digits require in the target radix.
     * @param integer $count Number of digits in the original number
     * @param resource $source Source radix as number resource
     * @param resource $target Target radix as number resource
     * @return integer Number of digits required in the target base
     */
    private function countDigits($count)
    {
        $target = gmp_init($this->target->getRadix());
        $maxFraction = gmp_pow(gmp_init($this->source->getRadix()), $count);
        for ($i = 1; gmp_cmp(gmp_pow($target, $i), $maxFraction) < 0; $i++);
        return $i;
    }
}
