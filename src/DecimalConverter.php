<?php

namespace Riimu\Kit\BaseConversion;

/**
 * Converts numbers by using a mathematical algorithm that relies on integers.
 *
 * DecimalConverter employs arbitrary-precision integer arithmetic to first
 * convert the digits to decimal system and then to convert the digits to the
 * target base. DecimalConverter depends on the GMP extension to perform the
 * required arbitrary precision integer calculations.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DecimalConverter implements Converter
{
    /** @var integer Precision for fraction conversions */
    private $precision;

    /** @var NumberBase Number base used by provided numbers */
    private $source;

    /** @var NumberBase Number base used by returned numbers */
    private $target;

    /** @var string Number base used by GMP for standard conversions */
    private static $standardBase = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Creates a new instance of DecimalConverter.
     * @param NumberBase $source Number base used by the provided numbers
     * @param NumberBase $target Number base used by the returned numbers
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
        return $this->toBase($this->toDecimal($number));
    }

    public function convertFractions(array $number)
    {
        $target = gmp_init($this->target->getRadix());
        $dividend = $this->toDecimal($number);
        $divisor = $this->toDecimal(
            [$this->source->getDigit(1)] + array_fill(1, max(count($number), 1), $this->source->getDigit(0))
        );
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
     * Converts the number from source base to GMP resource.
     * @param integer[] $number List of digit values with least significant digit first
     * @return resource resulting number as a GMP resource
     */
    private function toDecimal(array $number)
    {
        if ($this->isStandardBase($this->source->getDigitList())) {
            return gmp_init(implode('', $this->source->canonizeDigits($number)), $this->source->getRadix());
        }

        $number = $this->source->getValues($number);
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
     * @return integer[] List of digit values for the converted number
     */
    private function toBase($decimal)
    {
        if ($this->isStandardBase($this->target->getDigitList())) {
            return $this->target->canonizeDigits(str_split(gmp_strval($decimal, $this->target->getRadix())));
        }

        $zero = gmp_init('0');
        $radix = gmp_init($this->target->getRadix());
        $result = [];

        while (gmp_cmp($decimal, $zero) > 0) {
            list($decimal, $modulo) = gmp_div_qr($decimal, $radix);
            $result[] = gmp_intval($modulo);
        }

        return $this->target->getDigits(empty($result) ? [0] : array_reverse($result));
    }

    /**
     * Tells if the digits match those used by GMP.
     * @param array $digits List of digits for the number base
     * @return boolean True if the digits match, false if they do not
     */
    private function isStandardBase(array $digits)
    {
        if (count($digits) > 62) {
            return false;
        }

        return $digits == str_split(substr(self::$standardBase, 0, count($digits)));
    }

    /**
     * Determines the number of digits required in the target base.
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

        return $digits - $this->precision;
    }
}
