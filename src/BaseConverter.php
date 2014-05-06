<?php

namespace Riimu\Kit\NumberConversion;

use Riimu\Kit\NumberConversion\Method\ConversionException;

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
    private $numberConverters;

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
        $this->numberConverters = [
            'Replace\StringReplaceConverter',
            'Replace\DirectReplaceConverter',
            'Decimal\GMPConverter',
            'Direct\DirectConverter',
            'Decimal\BCMathConverter',
            'Decimal\InternalConverter',
        ];

        $this->fractionConverters = [
            'Replace\StringReplaceConverter',
            'Replace\DirectReplaceConverter',
            'Decimal\GMPConverter',
            'Decimal\BCMathConverter',
            'Decimal\InternalConverter',
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
    public function setNumberConverters(array $converters)
    {
        $this->numberConverters = $converters;
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
     * Converts the provided integer part using the provided conversion methods.
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     * @throws \RuntimeException If no integer conversion library is applicable
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
     * Converts the provided fractional part using the provided conversion methods.
     * @param array $number Fractions to covert with most significant digit last
     * @return array The converted fractions with most significant digit last
     * @throws \RuntimeException If no fraction conversion library is applicable
     */
    public function convertFractions(array $number)
    {
        foreach ($this->fractionConverters as & $converter) {
            try {
                if ($converter instanceof Method\Decimal\AbstractDecimalConverter) {
                    $converter->setPrecision($this->precision);
                }
                return $this->loadConverter($converter)->convertFractions($number);
            } catch (ConversionException $ex) {
                // Just continue to next method
            }
        }

        throw new \RuntimeException("No applicable conversion method available");
    }

    /**
     * Lazyloads the given converter.
     * @param string $name Name of the converter
     * @return \Riimu\Kit\NumberConversion\Method\Converter Loaded converter
     * @throws \RuntimeException If the converter does not exist
     */
    private function loadConverter(& $name)
    {
        if (!($name instanceof Method\Converter)) {
            $class = 'Riimu\Kit\NumberConversion\Method\\' . $name;

            if (!class_exists($class)) {
                $class = $name;
            }
            if (!is_a($class, 'Riimu\Kit\NumberConversion\Method\Converter', true)) {
                throw new \RuntimeException("Invalid converter '$class'");
            }

            $instance = new $class($this->sourceBase, $this->targetBase);

            if ($instance instanceof Method\Decimal\AbstractDecimalConverter) {
                $instance->setPrecision($this->precision);
            }

            $name = $instance;
        }

        return $name;
    }
}
