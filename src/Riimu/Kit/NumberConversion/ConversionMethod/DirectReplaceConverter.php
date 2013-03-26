<?php

namespace Riimu\Kit\NumberConversion\ConversionMethod;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DirectReplaceConverter extends ReplaceConverter
{
    private $conversionTable;

    public function replace(array $number, $fractions = false)
    {
        $table = $this->getConversionTable();
        $size = count($table[0][0]);
        $sourceZero = $this->source->getDigit(0);
        $targetZero = $this->target->getDigit(0);

        if ($size > 1 && ($pad = count($number) % $size) > 0) {
            $pad = (count($number) + ($size - $pad)) * ($fractions ? 1 : -1);
            $number = array_pad($number, $pad, $sourceZero);
        }

        $replacements = [[]];

        foreach (array_chunk($number, $size) as $chunk) {
            $key = array_search($chunk, $table[0]);

            // Attempt to resolve case insensitivity
            if ($key === false) {
                $chunk = $this->source->getDigits($this->getDecimals($chunk));
                $key = array_search($chunk, $table[0]);
            }

            $replacements[] = $table[1][$key];
        }

        $result = call_user_func_array('array_merge', $replacements);

        while (!empty($result) && ($fractions ? end($result) : reset($result)) == $targetZero) {
            unset($result[key($result)]);
        }

        return empty($result) ? [$targetZero] : array_values($result);
    }

    public function getConversionTable()
    {
        if (!isset($this->conversionTable)) {
            $this->conversionTable = $this->buildConversionTable();
        }

        return $this->conversionTable;
    }

    private function buildConversionTable()
    {
        $reduce = $this->source->getRadix() > $this->target->getRadix();
        $max = $reduce ? $this->source : $this->target;
        $min = $reduce ? $this->target : $this->source;

        $numbers = $min->getNumbers();
        $last = $min->getRadix() - 1;
        $size = (int) log($max->getRadix(), $min->getRadix());
        $number = array_fill(0, $size, $numbers[0]);
        $next = array_fill(0, $size, 0);
        $chunks = [$number];

        for ($i = $max->getRadix(); $i > 1; $i--) {
            for ($j = $size - 1; $next[$j] == $last; $j--) {
                $number[$j] = $numbers[0];
                $next[$j] = 0;
            }

            $number[$j] = $numbers[++$next[$j]];
            $chunks[] = $number;
        }

        $table = [$chunks, array_chunk($max->getNumbers(), 1)];
        return $reduce ? array_reverse($table) : $table;
    }
}
