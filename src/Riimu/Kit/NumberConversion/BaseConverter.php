<?php

namespace Riimu\Kit\NumberConversion;

use Riimu\Kit\NumberConversion\ConversionMethod\ConversionException;

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

    private $numberConverters;
    private $fractionConverters;
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
        $this->numberConverters = [
            'ConversionMethod\DirectReplaceConverter',
            'DecimalConverter\GMPConverter',
            'ConversionMethod\DirectConverter',
            'DecimalConverter\BCMathConverter',
            'DecimalConverter\InternalConverter',
        ];

        $this->fractionConverters = [
            'ConversionMethod\DirectReplaceConverter',
            'DecimalConverter\GMPConverter',
            'DecimalConverter\BCMathConverter',
            'DecimalConverter\InternalConverter',
        ];
    }

    public function setPrecision($precision)
    {
        $this->precision = (int) $precision;

        foreach (array_merge($this->numberConverters, $this->fractionConverters) as $converter) {
            if ($converter instanceof DecimalConverter\DecimalConverter) {
                $converter->setPrecision($this->precision);
            }
        }
    }

    public function setNumberConverters(array $converters)
    {
        $this->numberConverters = $converters;
    }

    public function setFractionConverters(array $converters)
    {
        $this->fractionConverters = $converters;
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
     * The conversion will be performed using the fastest method possible.
     * Replace conversion will be preferred, if it is possible between the two
     * number bases. If replacement is not available, then decimal conversion
     * will be used, but only if the GMPConverter is available. Otherwise this
     * method will fall back to using direct conversion.
     *
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     */
    public function convertNumber(array $number)
    {
        foreach ($this->numberConverters as & $converter) {
            try {
                return $this->loadConverter($converter)->convertNumber($number);
            } catch (ConversionException $ex) {
                // Just continue to next method
            }
        }

        throw new \RuntimeException("No applicable conversion method available");
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
     * To change the precision in the resulting number, use getDecimalConverter()
     * to return the converter and DecimalConverter::setDefaultPrecision() to
     * set the precision. Note that this precision is ignore if replacement
     * conversion is used or the number can be accurately converted with less
     * digits than required by the precision.
     *
     * @param array $number Fractions to covert with most significant digit last
     * @return array The converted fractions with most significant digit last
     * @throws \RuntimeException If no decimal conversion library is available
     */
    public function convertFractions(array $number)
    {
        foreach ($this->fractionConverters as & $converter) {
            try {
                return $this->loadConverter($converter)->convertFractions($number);
            } catch (ConversionException $ex) {
                // Just continue to next method
            }
        }

        throw new \RuntimeException("No applicable conversion method available");
    }

    private function loadConverter(& $name)
    {
        if (!($name instanceof ConversionMethod\ConversionMethod)) {
            $class = 'Riimu\Kit\NumberConversion\\' . $name;

            if (class_exists($class)) {
                $instance = new $class($this->sourceBase, $this->targetBase);
            } else {
                $instance = new $name($this->sourceBase, $this->targetBase);
            }

            if ($instance instanceof DecimalConverter\DecimalConverter) {
                $instance->setPrecision($this->precision);
            }

            $name = $instance;
        }

        return $name;
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
}
