<?php

namespace Riimu\Kit\NumberConversion;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ReplaceConverter
{
    private $source;
    private $target;
    private $sourceConverter;
    private $targetConverter;
    private $conversionTable;

    public function __construct(NumberBase $source, NumberBase $target)
    {
        $root = $source->findCommonRadixRoot($target);

        if ($root === false || $source->hasStringConflict() || $target->hasStringConflict()) {
            throw new \InvalidArgumentException('Number bases not supported');
        }

        if ($root != $source->getRadix() && $root != $target->getRadix()) {
            $proxy = new NumberBase($root);
            $this->sourceConverter = new ReplaceConverter($source, $proxy);
            $this->targetConverter = new ReplaceConverter($proxy, $target);
        } else {
            $this->source = $source;
            $this->target = $target;
            $this->conversionTable = $this->buildConversionTable();
        }
    }

    private function buildConversionTable()
    {
        if ($this->source->getRadix() === $this->target->getRadix()) {
            return true;
        }

        $reduce = $this->source->getRadix() > $this->target->getRadix();
        $max = $reduce ? $this->source : $this->target;
        $min = $reduce ? $this->target : $this->source;

        $minDigits = $min->getDigitList();
        $maxDigits = $max->getDigitList();
        $last = $min->getRadix() - 1;
        $size = (int) log($max->getRadix(), $min->getRadix());
        $number = array_fill(0, $size, $minDigits[0]);
        $next = array_fill(0, $size, 0);
        $limit = $max->getRadix();
        $table = [];

        for ($i = 0; $i < $limit; $i++) {
            if ($i > 0) {
                for ($j = $size - 1; $next[$j] == $last; $j--) {
                    $number[$j] = $minDigits[0];
                    $next[$j] = 0;
                }

                $number[$j] = $minDigits[++$next[$j]];
            }

            if ($reduce) {
                $table[$maxDigits[$i]] = implode('', $number);
            } else {
                $table[implode('', $number)] = $maxDigits[$i];
            }
        }

        return $table;
    }

    public function convertInteger(array $number)
    {
        return $this->convert($number, false);
    }

    public function convertFractions(array $number)
    {
        return $this->convert($number, true);
    }

    public function convert(array $number, $fractions = false)
    {
        if ($this->conversionTable === true) {
            return $this->zeroTrim(
                $this->target->getDigits($this->source->getValues($number)),
                $fractions
            );
        } elseif (!isset($this->conversionTable)) {
            return $this->targetConverter->replace(
                $this->sourceConverter->replace($number, $fractions),
                $fractions
            );
        }

        return $this->replace($number, $fractions);
    }

    public function replace(array $number, $fractions = false)
    {
        return $this->zeroTrim($this->target->splitString(
            strtr(implode('', $this->zeroPad(
                $this->source->canonizeDigits($number), $fractions
            )), $this->conversionTable)
        ), $fractions);
    }

    protected function zeroPad(array $number, $right)
    {
        $log = (int) log($this->target->getRadix(), $this->source->getRadix());

        if ($log > 1 && count($number) % $log) {
            $pad = count($number) + ($log - count($number) % $log);
            $number = array_pad($number, $right ? $pad: -$pad, $this->source->getDigit(0));
        }

        return $number;
    }

    protected function zeroTrim(array $number, $right)
    {
        $zero = $this->target->getDigit(0);

        while (($right ? end($number) : reset($number)) === $zero) {
            unset($number[key($number)]);
        }

        return empty($number) ? [$zero] : array_values($number);
    }
}
