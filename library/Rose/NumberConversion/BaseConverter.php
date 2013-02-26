<?php

namespace Rose\NumberConversion;

/**
 * Converts numbers of arbitrary size from number base to another.
 *
 * PHP has a built in function base_convert that does most of what this
 * class does. There are, however, two key differences. BaseConverter can
 * handle numbers of arbitrary size, unlike base_convert, and the number bases
 * does not necessarily have to consist of only ranges 0..9 and A..Z.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 */
class BaseConverter
{
    /**
     * Base the number is converted from.
     * @var NumberBase
     */
    private $sourceBase;

    /**
     * Base the number is converted to.
     * @var NumberBase
     */
    private $targetBase;

    /**
     * Creates a new base base converter.
     * @param NumberBase $sourceBase Number base used by the original number.
     * @param NumberBase $targetBase Number base used by the resulting number.
     */
    public function __construct (NumberBase $sourceBase, NumberBase $targetBase)
    {
        $this->sourceBase = $sourceBase;
        $this->targetBase = $targetBase;
    }

    /**
     * Converts a number provided as a string.
     * @param string $number Number to convert
     * @return string The converted number
     */
    public function convertString ($number)
    {
        return implode('', $this->convert(str_split($number)));
    }

    /**
     * Converts the number provided as an array using best method.
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     */
    public function convert (array $number)
    {
        if ($this->sourceBase->findCommonRadixRoot($this->targetBase)) {
            return $this->convertViaCommonRoot($number);
        } elseif (function_exists('bcadd')) {
            return $this->convertViaDecimal($number);
        } else {
            // @codeCoverageIgnoreStart
            return $this->convertDirectly($number);
            // @codeCoverageIgnoreEnd
        }
    }
    
    public function convertViaCommonRoot (array $number)
    {
        $root = $this->sourceBase->findCommonRadixRoot($this->targetBase);
        
        if ($root === false) {
            throw new \InvalidArgumentException('No common root found');
        }
        
        // If the common root is the radix of either number base, do it directly
        if ($root === $this->sourceBase->getRadix() || $root == $this->targetBase->getRadix()) {
            return $this->convertByReplace($number);
        }
        
        $rootBase = new NumberBase($root);
        $source = new BaseConverter($this->sourceBase, $rootBase);
        $target = new BaseConverter($rootBase, $this->targetBase);
        return $target->convertByReplace($source->convertByReplace($number));
    }

    /**
     * Converts number from base to another by replacing characters.
     *
     * Conversion via character replacement can only be done if the bigger base
     * is a result of natural exponent of the smaller base.
     *
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     * @throws \InvalidArgumentException if the number bases cannot be converted
     */
    public function convertByReplace (array $number)
    {
        $table = $this->sourceBase->createConversionTable($this->targetBase);
        $size = strlen(key($table));
        $zero = $this->sourceBase->getFromDecimalValue(0);
        $pad = $size - (count($number) % $size);

        $number = implode('', $number);
        if ($pad) {
            $number = str_repeat($zero, $size - $pad) . $number;
        }
        $number = ltrim(strtr($number, $table), $zero);

        return str_split($number);
    }

    /**
     * Converts number from source base to target base using decimals.
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     */
    public function convertViaDecimal (array $number)
    {
        $decimal = $this->convertToDecimal($number);
        return $this->convertFromDecimal($decimal);
    }

    /**
     * Converts the given number from the source number base to decimal.
     * @param array $number Number to convert
     * @return string The resulting decimal number as string
     */
    public function convertToDecimal (array $number)
    {
        $power = 0;
        $decimal = '0';
        $sourceRadix = $this->sourceBase->getRadix();

        foreach (array_reverse($number) as $digit) {
            $decimalDigit = $this->sourceBase->getDecimalValue($digit);
            $decimal = bcadd($decimal, bcmul($decimalDigit, bcpow($sourceRadix, $power++)));
        }

        return $decimal;
    }

    /**
     * Converts from decimal number to the target number base.
     * @param string $decimal Decimal number as a string
     * @return array The resulting number as an array with most significant digit last
     * @throws \InvalidArgumentException If the number string doesn't only contain digits
     */
    public function convertFromDecimal ($decimal)
    {
        if (!ctype_digit($decimal)) {
            throw new \InvalidArgumentException('Decimal must consist of numbers only');
        }

        $number = array();
        $targetRadix = $this->targetBase->getRadix();

        while ($decimal !== '0') {
            $modulo = bcmod($decimal, $targetRadix);
            $decimal = bcdiv(bcsub($decimal, $modulo), $targetRadix);
            $number[] = $this->targetBase->getFromDecimalValue($modulo);
        }

        return empty($number) ? array('0') : array_reverse($number);
    }

    /**
     * Converts directly from the source number base to target number base.
     *
     * The difference between convert() and convertDirectly() is the convert()
     * first converts the number to decimal using BCMath and then converts the
     * number to the target number base from decimal using BCMath. This method
     * does not use BCMath and converts the number directly from base to another
     * without intermediate decimal conversion. The algorithm is slightly faster
     * but more obscure.
     *
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     */
    public function convertDirectly (array $number)
    {
        foreach ($number as $i => $digit) {
            $number[$i] = $this->sourceBase->getDecimalValue($digit);
        }

        $sourceRadix = $this->sourceBase->getRadix();
        $targetRadix = $this->targetBase->getRadix();
        $result = array();

        do {
            $first = true;
            $remainder = 0;

            foreach ($number as $i => $value) {
                $remainder = $value + $remainder * $sourceRadix;

                if ($remainder >= $targetRadix) {
                    $number[$i] = (int) ($remainder / $targetRadix);
                    $remainder = $remainder % $targetRadix;
                    $first = false;
                } elseif ($first) {
                    unset($number[$i]);
                } else {
                    $number[$i] = 0;
                }
            }

            $result[] = $this->targetBase->getFromDecimalValue($remainder);
        } while (!empty($number));

        return array_reverse($result);
    }

    /**
     * Converts number from another base with minimal overhead.
     *
     * Compared to the non static methods convert() and convertDirectly(), this
     * method performs even faster. There is no overhead from class creation and
     * the algorithm itself consists of only language constructs, which makes it
     * almost as fast as possible. Any of the arguments may be given as a string
     * or an array. The only difference it makes is that the resulted number
     * will be returned as an array, if the original number was an array and
     * as string otherwise.
     *
     * @param string|array $number The number to convert
     * @param string|array $sourceBase The number base for the original number
     * @param string|array $targetBase The number base for the resulting number
     * @return string|array|false Resulted number or false on error
     */
    public static function customConvert ($number, $sourceBase, $targetBase)
    {
        for ($sourceRadix = 0; isset($sourceBase[$sourceRadix]); $sourceRadix++) {
            $sourceMap[$sourceBase[$sourceRadix]] = $sourceRadix;
        }

        for ($targetRadix = 0; isset($targetBase[$targetRadix]); $targetRadix++);

        $numbers = array();

        for ($numberLength = 0; isset($number[$numberLength]); $numberLength++) {
            if (!isset($sourceMap[$number[$numberLength]])) {
                return false;
            }

            $numbers[$numberLength] = $sourceMap[$number[$numberLength]];
        }

        if ($sourceRadix < 2 || $targetRadix < 2 || $numberLength < 1) {
            return false;
        }

        $result = array();
        $resultLength = 0;
        $skip = 0;

        do {
            $remainder = 0;
            $first = true;

            for ($i = $skip; $i < $numberLength; $i++) {
                $remainder = $numbers[$i] + $remainder * $sourceRadix;

                if ($remainder >= $targetRadix) {
                    $numbers[$i] = (int) ($remainder / $targetRadix);
                    $remainder = $remainder % $targetRadix;
                    $first = false;
                } elseif ($first) {
                    $skip++;
                } else {
                    $numbers[$i] = 0;
                }
            }

            $result[$resultLength++] = $targetBase[$remainder];
        } while ($skip < $numberLength);

        $test = $number;
        $test[0] = '';
        $return = $test[0] === '' ? array() : ' ';

        for ($i = 0; $i < $resultLength; $i++) {
            $return[$i] = $result[$resultLength - $i - 1];
        }

        return $return;
    }
}
