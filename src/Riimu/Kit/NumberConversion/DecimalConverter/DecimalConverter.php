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
     * @param array $number List of digit values
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

    public function convertFraction(array $number, $sourceRadix, $targetRadix, $precision = -1)
    {
        $source = $this->init($sourceRadix);
        $target = $this->init($targetRadix);
        $dividend = $this->toDecimal($number, $source);
        $divisor = $this->toDecimal([1] + array_fill(1, count($number), 0), $source);
        $digits = $precision > 0 ? $precision
            : $this->countDigits($number, $source, $target) + abs($precision);
        $zero = $this->init('0');

        for ($i = 0; $i <= $digits && $this->cmp($dividend, $zero) > 0; $i++) {
            list($digit, $dividend) = $this->div($this->mul($dividend, $target), $divisor);
            $result[] = (int) $this->val($digit);
        }

        var_dump($i, $digits, $result);

        return $i > $digits ? $this->round($result, $targetRadix) : $result;
    }

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

    private function countDigits(array $number, $source, $target)
    {
        $maxFraction = $this->pow($source, count($number));
        for ($pow = 1; $this->cmp($this->pow($target, $pow), $maxFraction) <= 0; $pow++);
        return $pow + 1;
    }

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

        while (end($number) === 0) {
            array_pop($number);
        }

        return $number;
    }

    abstract protected function init($number);
    abstract protected function val($number);
    abstract protected function add($a, $b);
    abstract protected function mul($a, $b);
    abstract protected function pow($a, $b);
    abstract protected function div($a, $b);
    abstract protected function cmp($a, $b);
}
