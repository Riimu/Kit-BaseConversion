<?php

namespace Riimu\Kit\BaseConversion;

/**
 * Converts numbers using character replacement.
 *
 * ReplaceConverter converts numbers from base to another using a simple string
 * replacement strategy. In other words. The digits from one base is simply
 * replaced with digits from the other base. This strategy, however, is only
 * possible if the two number bases share a common root or if the target number
 * base is nth root of the source base. This is required, because it allows
 * a sequence of digits to be simply replaced with an appropriate sequence of
 * digits from the other number base.
 *
 * When possible, the replacement strategy offers considerable speed gains over
 * strategies that employ arbitrary-precision arithmetic as there is no need
 * to calculate anything.
 *
 * Note the replacement conversion strategy can always convert fractions
 * accurately. Setting the precision for ReplaceConverter has no effect at all
 * on the conversion results.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ReplaceConverter implements Converter
{
    /** @var NumberBase Number base used by provided numbers */
    private $source;

    /** @var NumberBase Number base used by returned numbers */
    private $target;

    /** @var ReplaceConverter Converter used to convert into common root base */
    private $sourceConverter;

    /** @var ReplaceConverter Converter used to convert from common root base */
    private $targetConverter;

    /** @var array<string,string> String replacement table for converting numbers */
    private $conversionTable;

    /**
     * Create new instance of ReplaceConverter.
     *
     * ReplaceConverter only supports number base combinations that have a
     * common root or if the target base is nth root of the source base. In
     * addition, due to using string replacement, any number base that has
     * conflicting string digits are not supported.
     *
     * @param NumberBase $source Number base used by the provided numbers
     * @param NumberBase $target Number base used by the returned numbers
     * @throws InvalidNumberBaseException If the number bases are not supported
     */
    public function __construct(NumberBase $source, NumberBase $target)
    {
        $root = $this->getRoot($source, $target);

        if ($root !== $source->getRadix() && $root !== $target->getRadix()) {
            $proxy = new NumberBase($root);
            $this->sourceConverter = new self($source, $proxy);
            $this->targetConverter = new self($proxy, $target);
        } else {
            $this->source = $source;
            $this->target = $target;
            $this->conversionTable = $this->buildConversionTable();
        }
    }

    /**
     * Determines the common root for the number bases.
     * @param NumberBase $source Number base used by the provided numbers
     * @param NumberBase $target Number base used by the returned numbers
     * @return int The common root for the number bases
     * @throws InvalidNumberBaseException If the number bases are not supported
     */
    private function getRoot(NumberBase $source, NumberBase $target)
    {
        if ($source->hasStringConflict() || $target->hasStringConflict()) {
            throw new InvalidNumberBaseException('Number bases do not support string presentation');
        }

        $root = $source->findCommonRadixRoot($target);

        if ($root === false) {
            throw new InvalidNumberBaseException('No common root exists between number bases');
        }

        return $root;
    }

    /**
     * Creates string replacement table between source base and target base.
     * @return array<string,string> String replacement table for converting numbers
     */
    private function buildConversionTable()
    {
        if ($this->source->getRadix() > $this->target->getRadix()) {
            return $this->createTable($this->source->getDigitList(), $this->target->getDigitList());
        }

        return array_flip($this->createTable($this->target->getDigitList(), $this->source->getDigitList()));
    }

    /**
     * Creates a conversion table between two lists of digits.
     * @param string[] $source Digits for the number base with larger number of digits
     * @param string[] $target Digits for the number base with smaller number of digits
     * @return array<string,string> String replacement table for converting numbers
     */
    private function createTable($source, $target)
    {
        $last = count($target) - 1;
        $size = (int) log(count($source), count($target));
        $number = array_fill(0, $size, $target[0]);
        $next = array_fill(0, $size, 0);
        $limit = count($source);
        $table = [$source[0] => implode('', $number)];

        for ($i = 1; $i < $limit; $i++) {
            for ($j = $size - 1; $next[$j] === $last; $j--) {
                $number[$j] = $target[0];
                $next[$j] = 0;
            }

            $number[$j] = $target[++$next[$j]];
            $table[$source[$i]] = implode('', $number);
        }

        return $table;
    }

    public function setPrecision($precision)
    {
        // Replace converter always converts accurately
    }

    public function convertInteger(array $number)
    {
        return $this->convert($number, false);
    }

    public function convertFractions(array $number)
    {
        return $this->convert($number, true);
    }

    /**
     * Converts the digits from source base to target base.
     * @param array $number The digits to convert
     * @param bool $fractions True if converting fractions, false if not
     * @return array The digits converted to target base
     */
    private function convert(array $number, $fractions = false)
    {
        if (!isset($this->conversionTable)) {
            return $this->targetConverter->replace(
                $this->sourceConverter->replace($number, $fractions),
                $fractions
            );
        }

        return $this->replace($number, $fractions);
    }

    /**
     * Replace digits using string replacement.
     * @param array $number The digits to convert
     * @param bool $fractions True if converting fractions, false if not
     * @return array The digits converted to target base
     */
    private function replace(array $number, $fractions = false)
    {
        return $this->zeroTrim($this->target->splitString(strtr(implode('', $this->zeroPad(
            $this->source->canonizeDigits($number),
            $fractions
        )), $this->conversionTable)), $fractions);
    }

    /**
     * Pads the digits to correct count for string replacement.
     * @param array $number Array of digits to pad
     * @param bool $right True to pad from right, false to pad from left
     * @return array Padded array of digits
     */
    private function zeroPad(array $number, $right)
    {
        $log = (int) log($this->target->getRadix(), $this->source->getRadix());

        if ($log > 1 && count($number) % $log) {
            $pad = count($number) + ($log - count($number) % $log);
            $number = array_pad($number, $right ? $pad : -$pad, $this->source->getDigit(0));
        }

        return $number;
    }

    /**
     * Trims extraneous zeroes from the digit list.
     * @param array $number Array of digits to trim
     * @param bool $right True to trim from right, false to trim from left
     * @return array Trimmed array of digits
     */
    private function zeroTrim(array $number, $right)
    {
        $zero = $this->target->getDigit(0);

        while (($right ? end($number) : reset($number)) === $zero) {
            unset($number[key($number)]);
        }

        return empty($number) ? [$zero] : array_values($number);
    }
}
