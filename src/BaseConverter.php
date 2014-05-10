<?php

namespace Riimu\Kit\NumberConversion;

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
    private $converter;

    /**
     * The number precision for fraction converters.
     * @var integer
     */
    private $precision;

    private $source;
    private $target;

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
        $this->source = $sourceBase instanceof NumberBase
            ? $sourceBase : new NumberBase($sourceBase);
        $this->target = $sourceBase instanceof NumberBase
            ? $targetBase : new NumberBase($targetBase);

        try {
            $this->converter = new ReplaceConverter($this->source, $this->target);
        } catch (\InvalidArgumentException $ex) {
            $this->converter = new MathConverter($this->source, $this->target);
        }

        $this->precision = -1;
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
     * Converts the number from source base to target base.
     *
     * This method will automatically handle signed numbers and fractions.
     * If the number is preceded by either '+' or '-', the appropriate sign will
     * be added to the resulting number. Additionally, if the number contains
     * the decimal separator '.', the digits after that will be converted as
     * fractions.
     *
     * @param string $number The number to convert
     * @return string The converted number
     */
    public function convert ($number)
    {
        $integer = (string) $number;
        $dot = strpos($integer, '.');
        $sign = substr($integer, 0, 1);

        if ($dot !== false) {
            $fractions = substr($integer, $dot + 1);
            $integer = substr($integer, 0, $dot);
        }
        if ($sign === '+' || $sign === '-') {
            $integer = substr($integer, 1);
        }

        try {
            $integer = $this->source->splitString($integer);

            if ($dot !== false) {
                $fractions = $this->source->splitString($fractions);
            }
        } catch (\InvalidArgumentException $ex) {
            return false;
        }

        return ($sign === '+' || $sign === '-' ? $sign : '') .
            implode('', $this->convertInteger($integer)) .
            ($dot !== false ? '.' . implode('', $this->convertFractions($fractions)) : '');
    }

    /**
     * Converts the provided integer from source base to target base.
     *
     * The number can be provided as either an array with least significant
     * digit first or as a string. The return value will be in the same format
     * as the input value.
     *
     * @param array|string $number Integer to covert with least significant digit first
     * @return array|string The converted integer with least significant digit first
     * @throws \RuntimeException If no applicable integer converter is available
     */
    public function convertInteger(array $number)
    {
        return $this->converter->convertInteger($number);
    }

    /**
     * Converts the provided fractions from source base to target base.
     *
     * The number can be provided as either an array with least significant
     * digit first or as a string. The return value will be in the same format
     * as the input value.
     *
     * @param array|string $number Fractions to covert with least significant digit first
     * @return array|string The converted fractions with least significant digit first
     * @throws \RuntimeException If no applicable fraction converter is available
     */
    public function convertFractions(array $number)
    {
        if ($this->converter instanceof MathConverter) {
            $this->converter->setPrecision($this->precision);
        }

        return $this->converter->convertFractions($number);
    }
}
