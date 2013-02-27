<?php

namespace Rose\NumberConversion;

/**
 * Converts numbers of arbitrary size from number base to another.
 *
 * PHP's built in base_convert can handle base conversion needed in most cases.
 * There are however two special cases where the function is not sufficient.
 * First, the build in function can't handle numbers that require bigger than 32
 * bit integer to convert. Secondly, base_convert only supports number bases up
 * to 36. BaseConverter is capable of handling integers of any size in addition
 * to being able to handle very large number bases.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
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
     * Library used to perform decimal conversion
     * @var \Rose\NumberConversion\DecimalConverter\DecimalConverter
     */
    private $decimalConverter;

    /**
     * Creates a new base base converter.
     * @param NumberBase $sourceBase Number base used by the original number.
     * @param NumberBase $targetBase Number base used by the resulting number.
     */
    public function __construct (NumberBase $sourceBase, NumberBase $targetBase)
    {
        $this->sourceBase = $sourceBase;
        $this->targetBase = $targetBase;
        
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
     * It's possible to call the method with null parameter in order to disable
     * the decimal converter used by default.
     * 
     * @param \Rose\NumberConversion\DecimalConverter\DecimalConverter $converter Converter to use
     */
    public function setDecimalConverter (DecimalConverter\DecimalConverter $converter = null)
    {
        $this->decimalConverter = $converter;
    }

    /**
     * Converts the number provided as a string using best available method.
     * @param string $number Number to convert
     * @return string The converted number
     */
    public function convertString ($number)
    {
        return implode('', $this->convert(str_split($number)));
    }

    /**
     * Converts the number provided as an array using the best available method.
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     */
    public function convert (array $number)
    {
        if ($this->sourceBase->findCommonRadixRoot($this->targetBase)) {
            return $this->convertViaCommonRoot($number);
        } elseif ($this->decimalConverter !== null) {
            return $this->convertViaDecimal($number);
        } else {
            return $this->convertDirectly($number);
        }
    }
    
    /**
     * Converts the number by replacing numbers via a common radix root.
     * 
     * This method will attempt a find a root that is common to both the source
     * and target radix. If one is found, the number is first converted to a
     * number base with radix equal to the common root and then from that number
     * base to the target number base. Replacement method is fast enough that
     * doing it twice is still faster than any other method.
     * 
     * If no common root is found, an exception is thrown. If the bases are
     * exponential, then only one conversion will take place. 
     * 
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     * @throws \InvalidArgumentException If no common root can be found
     */
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
     * Converts number from base to another by simply replacing the numbers.
     *
     * If two number bases are exponential (i.e. one number can be represented
     * by exactly n numbers in the other base), then conversion can be done by
     * simply replacing the original numbers with numbers from the other base.
     * For large numbers, this is several magnitudes faster than any other
     * conversion method. Exception is thrown if the number bases are not
     * exponential.
     *
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     * @throws \InvalidArgumentException if the number bases cannot be converted
     */
    public function convertByReplace (array $number)
    {
        $table = $this->sourceBase->createConversionTable($this->targetBase);
        $size = count($table[0][0]);
        $sourceZero = $this->sourceBase->getFromDecimalValue(0);
        $targetZero = $this->targetBase->getFromDecimalValue(0);
        $pad = $size - (count($number) % $size);

        while ($pad--) {
            array_unshift($number, $sourceZero);
        }
        
        $replacements = array();
        
        foreach (array_chunk($number, $size) as $chunk) {
            $key = array_search($chunk, $table[0]);
            
            if ($key === false) {
                throw new \InvalidArgumentException('Invalid number');
            }
            
            $replacements[] = $table[1][$key];
        }
        
        $result = call_user_func_array('array_merge', $replacements);
        
        while ($result[0] == $targetZero && isset($result[1])) {
            array_shift($result);
        }

        return $result;
    }

    /**
     * Converts number from source base to target base using decimals.
     * @param array $number Number to covert with most significant digit last
     * @return array The converted number with most significant digit last
     */
    public function convertViaDecimal (array $number)
    {
        if ($this->decimalConverter === null) {
            throw new \RuntimeException('No decimal conversion library available');
        }
        
        $result = $this->decimalConverter->convertNumber(
            array_map(array($this->sourceBase, 'getDecimalValue'), $number),
            $this->sourceBase->getRadix(), $this->targetBase->getRadix());

        return array_map(array($this->targetBase, 'getFromDecimalValue'), $result);
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
