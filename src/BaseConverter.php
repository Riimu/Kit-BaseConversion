<?php

namespace Riimu\Kit\NumberConversion;

use Riimu\Kit\NumberConversion\Converter\ConversionException;

/**
 * Arbitrary precision number base conversion library.
 *
 * BaseConverter provides a convenient way to convert numbers of arbitrary size
 * using the different base converters provided by this library. BaseConverter
 * processes the number using appropriate converters and handles both integer
 * and fraction parts provided in a string.
 *
 * BaseConverter can be easily used as a replacement for PHP's built in
 * base_convert, except for that fact that BaseConverter's convert method is not
 * limited by double precision and can also convert fractions.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BaseConverter
{
    /**
     * Numeral system used by provided numbers.
     * @var NumberBase
     */
    private $sourceBase;

    /**
     * Numeral system used by returned numbers.
     * @var NumberBase
     */
    private $targetBase;

    /**
     * List of integer converters.
     * @var array
     */
    private $integerConverters;

    /**
     * List of fraction converters.
     * @var array
     */
    private $fractionConverters;

    /**
     * The number precision for fraction converters.
     * @var integer
     */
    private $precision;

    /**
     * Creates a new instance of BaseConverter.
     *
     * The source and target number base can be provided as an instance of
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

        $this->precision = -1;
        $this->integerConverters = [
            'Riimu\Kit\NumberConversion\Converter\Replace\StringReplaceConverter',
            'Riimu\Kit\NumberConversion\Converter\Replace\DirectReplaceConverter',
            'Riimu\Kit\NumberConversion\Converter\Decimal\GMPConverter',
            'Riimu\Kit\NumberConversion\Converter\Direct\DirectConverter',
            'Riimu\Kit\NumberConversion\Converter\Decimal\BCMathConverter',
            'Riimu\Kit\NumberConversion\Converter\Decimal\InternalConverter',
        ];

        $this->fractionConverters = [
            'Riimu\Kit\NumberConversion\Converter\Replace\StringReplaceConverter',
            'Riimu\Kit\NumberConversion\Converter\Replace\DirectReplaceConverter',
            'Riimu\Kit\NumberConversion\Converter\Decimal\GMPConverter',
            'Riimu\Kit\NumberConversion\Converter\Decimal\BCMathConverter',
            'Riimu\Kit\NumberConversion\Converter\Decimal\InternalConverter',
        ];
    }



    /**
     * Sets the precision used when converting fractions.
     *
     * If the precision is positive, it defines the maximum number of digits in
     * fractions. If the value is 0, the converted numbers have at least as many
     * digits as is required to represent the number in the same accuracy. A
     * negative precision simply increases the number of digits in addition to
     * what is required for same accuracy.
     *
     * The precision may be ignored if the converter can convert the fractions
     * accurately. The purpose of precision is to limit the number of digits in
     * cases where this is not possible.
     *
     * @param integer $precision Precision used when converting fractions
     */
    public function setPrecision($precision)
    {
        $this->precision = (int) $precision;
    }

    /**
     * Sets the list of integer converters to use.
     * @param array $converters Array of integer converter class names.
     */
    public function setIntegerConverters(array $converters)
    {
        $this->integerConverters = $converters;
    }

    /**
     * Sets the list of fraction converters to use.
     * @param array $converters Array of fraction converter class names.
     */
    public function setFractionConverters(array $converters)
    {
        $this->fractionConverters = $converters;
    }

    /**
     * Converts the number from source base to target base.
     *
     * The number can be provided as either an array with least significant
     * digit first or as a string. The return value will be in the same format
     * as the input value.
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
        $source = $number
            ? (is_array($number) ? $number : $this->sourceBase->splitString($number))
            : [];

        if (isset($source[0]) && ($source[0] === '-' || $source[0] === '+')) {
            if (!$this->sourceBase->hasDigit($source[0])) {
                $sign = array_shift($source);
            }
        }
        if (in_array('.', $source, true) && !$this->sourceBase->hasDigit('.')) {
            $fractions = array_slice(array_splice($source, array_search('.', $source, true)), 1);
        }

        $result = $this->convertInteger($source);

        if (isset($sign)) {
            array_unshift($result, $sign);
        }
        if (isset($fractions)) {
            $result[] = '.';
            $result = array_merge($result, $this->convertFractions($fractions));
        }

        return is_array($number) ? $result : implode('', $result);
    }



    /**
     * Converts the provided integer from source base to target base.
     * @param array $number Integer to covert with most significant digit last
     * @return array The converted integer with most significant digit last
     * @throws \RuntimeException If no applicable integer converter is available
     */
    public function convertInteger(array $number)
    {
        foreach ($this->integerConverters as $key => $converter) {
            try {
                if (is_string($converter)) {
                    $converter = $this->integerConverters[$key] = new $converter;

                    if (!($converter instanceof Converter\IntegerConverter)) {
                        throw new \RuntimeException('Invalid integer converter ' . get_class($converter));
                    }
                }

                $converter->setNumberBases($this->sourceBase, $this->targetBase);
                return $converter->convertInteger($number);
            } catch (ConversionException $ex) { }
        }

        throw new \RuntimeException("No applicable integer converter available");
    }

    /**
     * Converts the provided fractions from source base to target base.
     * @param array $number Fractions to covert with most significant digit last
     * @return array The converted fractions with most significant digit last
     * @throws \RuntimeException If no applicable fraction converter is available
     */
    public function convertFractions(array $number)
    {
        foreach ($this->fractionConverters as $key => $converter) {
            try {
                if (is_string($converter)) {
                    $converter = $this->fractionConverters[$key] = new $converter;

                    if (!($converter instanceof Converter\FractionConverter)) {
                        throw new \RuntimeException('Invalid fraction converter ' . get_class($converter));
                    }
                }

                $converter->setNumberBases($this->sourceBase, $this->targetBase);
                $converter->setPrecision($this->precision);
                return $converter->convertFractions($number);
            } catch (ConversionException $ex) { }
        }

        throw new \RuntimeException("No applicable fraction converter available");
    }
}
