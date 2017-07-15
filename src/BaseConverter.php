<?php

namespace Riimu\Kit\BaseConversion;

/**
 * Arbitrary precision number base converter.
 *
 * BaseConverter provides convenience to number conversion by providing a method
 * that accepts numbers as strings in addition to selecting the appropriate
 * number conversion strategy based on the provided number bases (which may also
 * be provided as constructor arguments for NumberBase instead of instances of
 * the said class).
 *
 * BaseConverter can also be used as a simple replacement for PHP's built in
 * `base_convert()` via the provided static method that accepts arguments
 * similar to the built in function in addition to providing the extra features
 * of this library (such as arbitrary precision conversion and support for
 * fractions).
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BaseConverter implements Converter
{
    /** @var Converter Selected converter for base conversion */
    private $converter;

    /** @var int Precision provided to the fraction converter */
    private $precision;

    /** @var NumberBase Number base used by provided numbers */
    private $source;

    /** @var NumberBase Number base used by returned numbers */
    private $target;

    /**
     * Creates a new instance of BaseConverter.
     *
     * The source and target number bases can be provided either as an instance
     * of the NumberBase class or as constructor arguments that are provided to
     * the NumberBase class.
     *
     * The constructor will select the most optimal conversion strategy based
     * on the provided number bases.
     *
     * @see NumberBase::__construct
     * @param mixed $sourceBase Number base used by the provided numbers
     * @param mixed $targetBase Number base used by the returned numbers
     */
    public function __construct($sourceBase, $targetBase)
    {
        $this->source = $sourceBase instanceof NumberBase ? $sourceBase : new NumberBase($sourceBase);
        $this->target = $sourceBase instanceof NumberBase ? $targetBase : new NumberBase($targetBase);

        try {
            $this->converter = new ReplaceConverter($this->source, $this->target);
        } catch (InvalidNumberBaseException $ex) {
            $this->converter = new DecimalConverter($this->source, $this->target);
        }

        $this->precision = -1;
    }

    /**
     * Converts the provided number from base to another.
     *
     * This method provides a convenient replacement to PHP's built in
     * `base_convert()`. The number bases are simply passed along to the
     * constructor, which means they can be instances of NumberBase class or
     * constructor parameters for that class.
     *
     * Note that due to the way the constructor parameters for NumberBase work,
     * this method can be used exactly the same way as `base_convert()`.
     *
     * @param string $number The number to convert
     * @param mixed $fromBase Number base used by the provided number
     * @param mixed $toBase Number base used by the returned number
     * @param int $precision Precision for inaccurate conversion
     * @return string|false The converted number or false on error
     */
    public static function baseConvert($number, $fromBase, $toBase, $precision = -1)
    {
        $converter = new self($fromBase, $toBase);
        $converter->setPrecision($precision);

        return $converter->convert($number);
    }

    /**
     * Converts the number provided as a string from source base to target base.
     *
     * This method provides convenient conversions by accepting the number as
     * a string. The number may optionally be preceded by a plus or minus sign
     * which is prepended to the result as well. The number may also have a
     * period, which separates the integer part and the fractional part.
     *
     * Due to the special meaning of `+`, `-` and `.`, it is not recommended to
     * use this method to convert numbers when using number bases that have a
     * meaning for these characters (such as base64).
     *
     * If the number contains invalid characters, the method will return false
     * instead.
     *
     * @param string $number The number to convert
     * @return string|false The converted number or false on error
     */
    public function convert($number)
    {
        $integer = (string) $number;
        $fractions = null;
        $sign = '';

        if (in_array(substr($integer, 0, 1), ['+', '-'], true)) {
            $sign = $integer[0];
            $integer = substr($integer, 1);
        }

        if (($pos = strpos($integer, '.')) !== false) {
            $fractions = substr($integer, $pos + 1);
            $integer = substr($integer, 0, $pos);
        }

        return $this->convertNumber($sign, $integer, $fractions);
    }

    /**
     * Converts the different parts of the number and handles invalid digits.
     * @param string $sign Sign that preceded the number or an empty string
     * @param string $integer The integer part of the number
     * @param string|null $fractions The fractional part of the number or null if none
     * @return string|false The converted number or false on error
     */
    private function convertNumber($sign, $integer, $fractions)
    {
        try {
            $result = implode('', $this->convertInteger($this->source->splitString($integer)));

            if ($fractions !== null) {
                $result .= '.' . implode('', $this->convertFractions($this->source->splitString($fractions)));
            }
        } catch (DigitList\InvalidDigitException $ex) {
            return false;
        }

        return $sign . $result;
    }

    public function setPrecision($precision)
    {
        $this->precision = (int) $precision;
    }

    public function convertInteger(array $number)
    {
        return $this->converter->convertInteger($number);
    }

    public function convertFractions(array $number)
    {
        $this->converter->setPrecision($this->precision);

        return $this->converter->convertFractions($number);
    }
}
