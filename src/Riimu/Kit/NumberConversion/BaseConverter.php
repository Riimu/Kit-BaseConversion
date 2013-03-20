<?php

namespace Riimu\Kit\NumberConversion;

/**
 * Converts positive integers of arbitrary size from number base to another.
 *
 * PHP's built in base_convert can handle base conversion needed in most cases.
 * There are however two special cases where the function is not sufficient.
 * First, the builtin function can't handle numbers that require bigger than 32
 * bit integer to convert. Secondly, base_convert only supports number bases up
 * to 36. BaseConverter is capable of handling positive integers of arbitrary
 * size and number bases larger than 36. In addition, the number bases can be
 * defined in more customizable manner. Note that all operations perfomed by
 * the BaseConverter are case sensitive.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BaseConverter
{
    /**
     * Number base for the original number.
     * @var NumberBase
     */
    private $sourceBase;

    /**
     * Number base for the resulting number.
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
     * BaseConverter used for source conversion via common root.
     * @var BaseConverter
     */
    private $intermediateSource;

    /**
     * BaseConverter used for target conversion via common root.
     * @var BaseConverter
     */
    private $intermediateTarget;

    /**
     * Replacement conversion table from source base to target base.
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
     * @param Mixed $sourceBase Number base used by the original number.
     * @param Mixed $targetBase Number base used by the resulting number.
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
            $this->decimalConverter = null;
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Sets the decimal converter library to use.
     *
     * The decimal converter library is determined by available functions in PHP
     * by default. You can use this method to change the decimal converter used.
     * Using the the value "null" will disable the decimal conversion method
     * from being used.
     *
     * @param DecimalConverter\DecimalConverter $converter Converter to use
     */
    public function setDecimalConverter (DecimalConverter\DecimalConverter $converter = null)
    {
        $this->decimalConverter = $converter;
    }

    /**
     * Converts the provided number using the best possible method.
     *
     * The number can be provided as either an array with least significant
     * digit first or as a string. The return value will be in the same format
     * as the input value. Note that using strings with number bases that
     * contain longer than single byte values may provide unexpected results.
     *
     * The conversion will be performed using the "best" available method. The
     * replacement conversion method is preferred, if possible between the two
     * number bases. If replacement is not available, decimal conversion is
     * used. If no decimal converter is available, then conversion will fall
     * back to manual conversion.
     *
     * @param array|string $number The number to convert
     * @return array|string The converted number
     */
    public function convert ($number)
    {
        $source = is_array($number) ? $number : str_split($number);

        if ($this->commonRoot !== false) {
            $result = $this->convertViaCommonRoot($source);
        } elseif ($this->decimalConverter !== null) {
            $result = $this->convertViaDecimal($source);
        } else {
            $result = $this->convertDirectly($source);
        }

        return is_array($number) ? $result : implode('', $result);
    }

    /**
     * Converts the number by replacing numbers via a common radix root.
     *
     * If a common root exists for both the source and target radix, then the
     * number can be converted by using convertByReplace() by converting it via
     * a number base with radix equal to the common root. Doing two replacement
     * conversion should still be faster in most cases than any other conversion
     * method.
     *
     * If no common root exists between the two number bases, an exception
     * will be thrown.
     *
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     * @throws \InvalidArgumentException If no common root exists
     */
    public function convertViaCommonRoot (array $number)
    {
        if ($this->commonRoot === false) {
            throw new \InvalidArgumentException('No common root exists');
        }

        // If the common root is the radix of either number base, do it directly
        if ($this->commonRoot === $this->sourceBase->getRadix() ||
            $this->commonRoot === $this->targetBase->getRadix()) {
            return $this->convertByReplace($number);
        }

        if ($this->intermediateSource === null) {
            $rootBase = new NumberBase($this->commonRoot);
            $this->intermediateSource = new BaseConverter($this->sourceBase, $rootBase);
            $this->intermediateTarget = new BaseConverter($rootBase, $this->targetBase);
        }

        return $this->intermediateTarget->convertByReplace(
            $this->intermediateSource->convertByReplace($number));
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
     * @return array The converted number with most significant digit last
     * @throws \InvalidArgumentException if replacement conversion is not possible
     */
    public function convertByReplace (array $number)
    {
        if ($this->conversionTable === null) {
            $this->conversionTable = $this->sourceBase->createConversionTable($this->targetBase);
        }

        $size = count($this->conversionTable[0][0]);
        $sourceZero = $this->sourceBase->getFromDecimalValue(0);
        $targetZero = $this->targetBase->getFromDecimalValue(0);
        $pad = $size - (count($number) % $size);

        while ($pad--) {
            array_unshift($number, $sourceZero);
        }

        $replacements = [];

        foreach (array_chunk($number, $size) as $chunk) {
            $key = array_search($chunk, $this->conversionTable[0]);

            if ($key === false) {
                throw new \InvalidArgumentException('Invalid number');
            }

            $replacements[] = $this->conversionTable[1][$key];
        }

        $result = call_user_func_array('array_merge', $replacements);

        while ($result[0] == $targetZero && isset($result[1])) {
            array_shift($result);
        }

        return $result;
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
     * @return array The converted number with most significant digit last
     */
    public function convertViaDecimal (array $number)
    {
        if ($this->decimalConverter === null) {
            throw new \RuntimeException('No decimal conversion library available');
        }

        $result = $this->decimalConverter->convertNumber(
            array_map([$this->sourceBase, 'getDecimalValue'], $number),
            $this->sourceBase->getRadix(), $this->targetBase->getRadix());

        return array_map([$this->targetBase, 'getFromDecimalValue'], $result);
    }

    /**
     * Converts directly from the source number base to target number base.
     *
     * Direct conversion converts the number by taking the decimal values of
     * the digits in the number and determining the reminder by using an
     * implementation of long division. Using this method, it is not required
     * to convert the number to decimal number in between and it avoids the
     * limits of 32 bit integers. Due manual implementation of long division,
     * this tends to be slowest of all the conversion methods (unless BCMath is
     * used).
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
     * Converts number directly from base to another with minimal overhead.
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

        if ($sourceRadix < 2 || $targetRadix < 2 || $numberLength < 1) {
            return false;
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
