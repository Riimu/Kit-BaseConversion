<?php

namespace Riimu\Kit\NumberConversion;

/**
 * Number conversion library for integers and fractions of arbitrary precision.
 *
 * BaseConverter provides more features in number base conversion than PHP's
 * builtin base_convert. This library supports numbers of arbitrary size, unlike
 * base_convert which is limited by 32 bit integers. In addition, conversion of
 * fractions is also supported up to unlimited precision. This library will also
 * attempt to make several optimizations regarding conversion to provide fastest
 * possible result. On top of it all, use of NumberBase class allows greater
 * customization in how number bases as represented in addition to supporting
 * number bases of arbitrary size.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BaseConverter
{
    /**
     * Number base used by provided numbers.
     * @var NumberBase
     */
    private $sourceBase;

    /**
     * Number base used by returned numbers.
     * @var NumberBase
     */
    private $targetBase;

    /**
     * Common root between the number bases, if any.
     * @var false|integer
     */
    private $commonRoot;

    /**
     * Library used to perform the decimal conversion.
     * @var DecimalConverter\DecimalConverter
     */
    private $decimalConverter;

    /**
     * BaseConverter used to convert input number to intermediate number.
     * @var BaseConverter
     */
    private $intermediateSource;

    /**
     * BaseConverter used to convert intermediate number to returned number.
     * @var BaseConverter
     */
    private $intermediateTarget;

    /**
     * Replacement conversion table between source base and target base.
     * @var array
     */
    private $conversionTable;

    /**
     * Creates a new instance of BaseConverter.
     *
     * The source and target number bases can be provided as an instance of
     * NumberBase or as a value provided to the NumberBase constructor. See the
     * NumberBase constructor for information about possible values.
     *
     * @see NumberBase::__construct
     * @param Mixed $sourceBase Number base used by the provided numbers.
     * @param Mixed $targetBase Number base used by the returned numbers.
     */
    public function __construct ($sourceBase, $targetBase)
    {
        $this->sourceBase = $sourceBase instanceof NumberBase
            ? $sourceBase : new NumberBase($sourceBase);
        $this->targetBase = $sourceBase instanceof NumberBase
            ? $targetBase : new NumberBase($targetBase);
        $this->commonRoot = $this->sourceBase->findCommonRadixRoot($this->targetBase);

        $this->intermediateSource = null;
        $this->intermediateTarget = null;
        $this->conversionTable = null;

        // @codeCoverageIgnoreStart
        if (function_exists('gmp_add')) {
            $this->decimalConverter = new DecimalConverter\GMPConverter();
        } elseif (function_exists('bcadd')) {
            $this->decimalConverter = new DecimalConverter\BCMathConverter();
        } else {
            $this->decimalConverter = new DecimalConverter\InternalConverter();
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Sets the decimal converter library to use.
     *
     * The default decimal conversion library is determined by the available
     * functions in the PHP installation. You can use this method to change the
     * decimal converter used. Using the the value "null" will disable the
     * decimal conversion method from being used.
     *
     * @param DecimalConverter\DecimalConverter $converter Decimal converter to use
     */
    public function setDecimalConverter (DecimalConverter\DecimalConverter $converter = null)
    {
        $this->decimalConverter = $converter;
    }

    /**
     * Returns the decimal converter currently in use.
     * @return DecimalConverter\DecimalConverter
     */
    public function getDecimalConverter()
    {
        return $this->decimalConverter;
    }

    /**
     * Converts the given number while taking care of fractions and negativity.
     *
     * The number can be provided as either an array with least significant
     * digit first or as a string. The return value will be in the same format
     * as the input value. Note that you may get unexpected results when using
     * strings if the source or target number base contains multibyte digits.
     *
     * This method will automatically handle negative numbers and fractions.
     * If the number is preceded by either '+' or '-', the appropriate sign will
     * be added to the resulting number. Additionally, if the number contains
     * the decimal separator '.', the digits after that will be converted as
     * fractions. The special meaning of these characters will be ignored,
     * however, if the characters are digits in the source base (e.g '+' being
     * part of base 64).
     *
     * @param array|string $number The number to convert
     * @return array|string The converted number
     */
    public function convert ($number)
    {
        $source = is_array($number) ? $number
            : ($number === '' ? [] : str_split($number));
        $signed = isset($source[0]) && in_array($source[0], ['-', '+']);
        $dot = array_search('.', $source);

        if ($dot !== false) {
            if ($this->sourceBase->hasDigit('.')) {
                $dot = false;
            } else {
                $fractions = array_slice($source, $dot + 1);
                $source = array_slice($source, 0, $dot);
            }
        }
        if ($signed) {
            if ($this->sourceBase->hasDigit($source[0])) {
                $signed = false;
            } else {
                $sign = array_shift($source);
            }
        }

        $result = $this->convertNumber($source);

        if ($signed) {
            array_unshift($result, $sign);
        }
        if ($dot !== false) {
            $result[] = '.';
            $result = array_merge($result, $this->convertFractions($fractions));
        }

        return is_array($number) ? $result : implode('', $result);
    }

    /**
     * Converts the provided integer using the best possible method.
     *
     * The conversion will be performed using the "best" available method. The
     * replacement conversion method is preferred, if possible between the two
     * number bases. If replacement is not available, decimal conversion is
     * used. If no decimal converter is available, then conversion will fall
     * back to manual conversion.
     *
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     */
    public function convertNumber(array $number)
    {
        if ($this->commonRoot !== false) {
            return $this->convertViaCommonRoot($number);
        } elseif ($this->decimalConverter instanceof DecimalConverter\GMPConverter) {
            return $this->convertViaDecimal($number);
        } else {
            return $this->convertDirectly($number);
        }
    }

    /**
     * Converts the provided fractional part using the best possible method.
     *
     * If replacement conversion is possible between the two number bases, that
     * will be preferred. Otherwise decimal conversion is used. If no decimal
     * conversion library is available, then an exception will be thrown as
     * fraction conversion is not implemented without a decimal conversion
     * library.
     *
     * @param array $number Fractions to covert with most significant digit last
     * @return array The converted fractions with most significant digit last
     * @throws \RuntimeException If no decimal conversion library is available
     */
    public function convertFractions(array $number)
    {
        if ($this->commonRoot !== false) {
            return $this->convertViaCommonRoot($number, true);
        } elseif ($this->decimalConverter !== null) {
            return $this->convertViaDecimal($number, true);
        } else {
            throw new \RuntimeException("Fraction conversion is not available without decimal converter");
        }
    }

    /**
     * Converts the number by replacing numbers via a common radix root.
     *
     * If a common root exists for both the source and target radix, then the
     * number can be converted by using convertByReplace() by converting it via
     * a number base with radix equal to the common root. Doing two replacement
     * conversion should still be faster in most cases than any other conversion
     * method. If no common root exists between the two number bases,
     * an exception will be thrown.
     *
     * @param array $number Number to covert with most significant digit last
     * @param boolean $fractions True if converting fractions, false if not
     * @return array The converted number with most significant digit last
     * @throws \InvalidArgumentException If no common root exists
     */
    public function convertViaCommonRoot (array $number, $fractions = false)
    {
        if ($this->commonRoot === false) {
            throw new \InvalidArgumentException('No common root exists');
        }

        // If the common root is the radix of either number base, do it directly
        if ($this->commonRoot === $this->sourceBase->getRadix() ||
            $this->commonRoot === $this->targetBase->getRadix()) {
            return $this->convertByReplace($number, $fractions);
        }

        if ($this->intermediateSource === null) {
            $rootBase = new NumberBase($this->commonRoot);
            $this->intermediateSource = new BaseConverter($this->sourceBase, $rootBase);
            $this->intermediateTarget = new BaseConverter($rootBase, $this->targetBase);
        }

        return $this->intermediateTarget->convertByReplace(
            $this->intermediateSource->convertByReplace($number, $fractions),
            $fractions);
    }

    /**
     * Converts number from base to another by simply replacing the numbers.
     *
     * If the radix of either number base is nth root for the other base, then
     * conversion can be performed by simply replacing the digits with digits
     * from the target base. No calculation logic is required, which makes this
     * the fastest conversion method by far. A slight overhead is caused on the
     * first conversion by generation of the number conversion table. An
     * exception is thrown if replacement conversion cannot be performed between
     * the two number bases.
     *
     * @param array $number Number to covert with most significant digit last
     * @param boolean $fractions True if converting fractions, false if not
     * @return array The converted number with most significant digit last
     * @throws \InvalidArgumentException if replacement conversion is not possible
     */
    public function convertByReplace (array $number, $fractions = false)
    {
        if ($this->conversionTable === null) {
            $this->conversionTable = $this->sourceBase->createConversionTable($this->targetBase);
        }

        $size = count($this->conversionTable[0][0]);
        $sourceZero = $this->sourceBase->getFromDecimalValue(0);
        $targetZero = $this->targetBase->getFromDecimalValue(0);

        $pad = count($number) + ($size - (count($number) % $size ?: $size));
        $number = array_pad($number, $pad * ($fractions ? +1: -1), $sourceZero);

        $replacements = [[]];

        foreach (array_chunk($number, $size) as $chunk) {
            $key = array_search($chunk, $this->conversionTable[0]);

            if ($key === false) {
                throw new \InvalidArgumentException('Invalid number');
            }

            $replacements[] = $this->conversionTable[1][$key];
        }

        $result = call_user_func_array('array_merge', $replacements);

        while (!empty($result) && ($fractions ? end($result) : reset($result)) == $targetZero) {
            unset($result[key($result)]);
        }

        return empty($result) ? [$targetZero] : array_values($result);
    }

    /**
     * Converts number from base to another using arbitrary size integer logic.
     *
     * Decimal conversion takes advantage of arbitrary size integer libraries
     * to first convert the source number into decimal and then converting that
     * number into the target base. The speed of this method depends entirely
     * on the integer library used. From the implemented libraries, GMP is
     * several magnitudes faster than BCMath. Using BCMath, this is the slowest
     * conversion method. Using GMP, this is not much slower than replacement
     * conversion.
     *
     * @param array $number Number to covert with most significant digit last
     * @param boolean $fractions True if converting fractions, false if not
     * @return array The converted number with most significant digit last
     */
    public function convertViaDecimal (array $number, $fractions = false)
    {
        if ($this->decimalConverter === null) {
            throw new \RuntimeException('No decimal conversion library available');
        }

        $method = $fractions ? 'convertFractions' : 'convertNumber';
        $result = $this->decimalConverter->$method(
            array_map([$this->sourceBase, 'getDecimalValue'], $number),
            $this->sourceBase->getRadix(), $this->targetBase->getRadix());

        return array_map([$this->targetBase, 'getFromDecimalValue'], $result);
    }

    /**
     * Converts numbers directly from base to another.
     *
     * Direct conversion converts the number by taking the decimal values of
     * the digits in the number and determining the reminder by using an
     * implementation of long division. Using this method, it is not required
     * to convert the number to decimal number in between and it avoids the
     * limits of 32 bit integers. Due manual implementation of long division,
     * this tends to be slowest of all the conversion methods (unless BCMath is
     * used). Direct conversion cannot be used to convert fractional part.
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
        $result = [];

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
     * Converts integers directly from base to another with minimal overhead.
     *
     * Any of the arguments may be provided as a string or an array with the
     * least significant digit first. For example, using 'A09FF' as the number,
     * '0123456789ABCDEF' as the source base and '01234567' as the target base
     * will return '2404777'. The method will return a string or an array
     * depending on the type of the input number.
     *
     * The logic of this method is essentially the same as convertDirectly(),
     * except that there is no function call overhead as the method only uses
     * language constructs. This makes it slightly faster than
     * convertDirectly(), but it does not take advantage of the NumberBase
     * class. This method exists mostly for vanity reasons providing a silly
     * example of using strings and arrays interchangeably.
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

        $numbers = [];

        for ($numberLength = 0; isset($number[$numberLength]); $numberLength++) {
            if (!isset($sourceMap[$number[$numberLength]])) {
                return false;
            }

            $numbers[$numberLength] = $sourceMap[$number[$numberLength]];
        }

        if ($sourceRadix < 2 || $targetRadix < 2) {
            return false;
        } elseif ($numberLength < 1) {
            return $targetBase[0];
        }

        $result = [];
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

        // Essentially is_string() using language construct
        $test = $number;
        $test[0] = '';
        $return = $test[0] === '' ? [] : ' ';

        for ($i = 0; $i < $resultLength; $i++) {
            $return[$i] = $result[$resultLength - $i - 1];
        }

        return $return;
    }
}
