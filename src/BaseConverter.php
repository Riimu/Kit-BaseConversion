<?php

namespace Riimu\Kit\BaseConversion;

/**
 * Arbitrary precision number base converter.
 *
 * BaseConverter provides convenient number base conversion by accepting string
 * input, choosing the appropriate conversion strategy and creating the
 * number bases for you.
 *
 * BaseConverter can be used as an easy replacement for PHP's built in
 * base_convert in addition to providing more features such as converting
 * fractions and not being limited by internal floating point calculations.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BaseConverter implements Converter
{
    /**
     * Converter used to convert the numbers from base to another.
     * @var Converter
     */
    private $converter;

    /**
     * The number precision for fraction converters.
     * @var integer
     */
    private $precision;

    /**
     * Number base for provided numbers.
     * @var NumberBase
     */
    private $source;

    /**
     * Number base for returned numbers.
     * @var NumberBase
     */
    private $target;

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
        $this->source = $sourceBase instanceof NumberBase ? $sourceBase : new NumberBase($sourceBase);
        $this->target = $sourceBase instanceof NumberBase ? $targetBase : new NumberBase($targetBase);

        try {
            $this->converter = new ReplaceConverter($this->source, $this->target);
        } catch (\InvalidArgumentException $ex) {
            $this->converter = new DecimalConverter($this->source, $this->target);
        }

        $this->precision = -1;
    }

    /**
     * Converts number string from source base to target base.
     *
     * This method takes the number as a string that can contain both the
     * integer part and fractional part separated by the decimal operator '.'.
     * Additionally, this method will handle signed numbers (preceded by either
     * '+' or '-') by adding the appropriate sign to the returned number.
     *
     * Note that number bases that contain any of these characters as digits
     * (such as base64) may not work as intended due to special meaning added
     * to these characters.
     *
     * If the provided number contains any digits that are not part of the
     * number base, false will be returned instead.
     *
     * @param string $number The number to convert
     * @return string|false The converted number or false on error
     */
    public function convert ($number)
    {
        $integer = (string) $number;
        $fractions = '';
        $sign = '';

        if (isset($integer[0]) && in_array($integer[0], ['+', '-'])) {
            $sign = $integer[0];
            $integer = substr($integer, 1);
        }

        try {
            if (($pos = strpos($integer, '.')) !== false) {
                $fractions = '.' . implode('', $this->convertFractions(
                    $this->source->splitString(substr($integer, $pos + 1))
                ));
                $integer = substr($integer, 0, $pos);
            }

            $integer = implode('', $this->convertInteger($this->source->splitString($integer)));
        } catch (\InvalidArgumentException $ex) {
            return false;
        }

        return $sign . $integer . $fractions;
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
