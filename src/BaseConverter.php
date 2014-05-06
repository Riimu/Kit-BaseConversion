<?php

namespace Riimu\Kit\NumberConversion;

use Riimu\Kit\NumberConversion\Converter\ConversionException;

/**
 * Number conversion library for numbers of arbitrary size.
 *
 * BaseConverter provides more comprehensive approach to converting numbers from
 * base to another than PHP's built in base_convert. This library is not limited
 * by 32 bit integers in addition to being able to convert fractions. The
 * library also uses various conversion strategies to obtain optimal results
 * when converting large numbers. Using NumberBase class, it is also possible
 * define highly customized number bases of any size.
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
     * List of number conversion methods.
     * @var array
     */
    private $integerConverters;

    /**
     * List of conversion methods used to convert fractions.
     * @var array
     */
    private $fractionConverters;

    /**
     * The number precision for fraction conversion methods.
     * @var integer
     */
    private $precision;

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
     * It's not always possible to convert fractions accurately from base to
     * another. This method can be used to set the precision, i.e. the number
     * of digits after the decimal separator in the returned numbers. A positive
     * number indicates exact number of digits. A negative number indicates
     * a number of digits in addition to what is required to have at least the
     * same precision in the resulted number.
     *
     * @param integer $precision Precision used when converting fractions
     */
    public function setPrecision($precision)
    {
        $this->precision = (int) $precision;
    }

    /**
     * Sets the list objects used for integer conversion.
     * @param array $converters Array of integer conversion objects.
     */
    public function setIntegerConverters(array $converters)
    {
        $this->integerConverters = $converters;
    }

    /**
     * Sets the list of objects used for fraction conversion.
     * @param array $converters Array of fraction conversion objects.
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
        $source = $number ? (is_array($number) ? $number : str_split($number)) : [];

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
     * Converts the provided integer part using the provided conversion methods.
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     * @throws \RuntimeException If no integer conversion library is applicable
     */
    public function convertInteger(array $number)
    {
        foreach ($this->integerConverters as $key => $converter) {
            try {
                if (is_string($converter)) {
                    $converter = $this->integerConverters[$key] =
                        new $converter($this->sourceBase, $this->targetBase);

                    if (!($converter instanceof Converter\IntegerConverter)) {
                        throw new \RuntimeException('Invalid converter class ' . get_class($converter));
                    }
                }

                return $converter->convertInteger($number);
            } catch (ConversionException $ex) {
                // Just continue to next method
            }
        }

        throw new \RuntimeException("No applicable conversion method available");
    }

    /**
     * Converts the provided fractional part using the provided conversion methods.
     * @param array $number Fractions to covert with most significant digit last
     * @return array The converted fractions with most significant digit last
     * @throws \RuntimeException If no fraction conversion library is applicable
     */
    public function convertFractions(array $number)
    {
        foreach ($this->fractionConverters as $key => $converter) {
            try {
                if (is_string($converter)) {
                    $converter = $this->fractionConverters[$key] =
                        new $converter($this->sourceBase, $this->targetBase);

                    if (!($converter instanceof Converter\FractionConverter)) {
                        throw new \RuntimeException('Invalid converter class ' . get_class($converter));
                    }
                }

                $converter->setPrecision($this->precision);
                return $converter->convertFractions($number);
            } catch (ConversionException $ex) {
                // Just continue to next method
            }
        }

        throw new \RuntimeException("No applicable conversion method available");
    }
}
