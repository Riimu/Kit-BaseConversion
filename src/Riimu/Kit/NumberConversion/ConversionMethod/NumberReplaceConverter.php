<?php

namespace Riimu\Kit\NumberConversion\ConversionMethod;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class NumberReplaceConverter extends ReplaceConverter
{
    private $conversionTable;

    public function replace(array $number, $fractions = false)
    {
        $number = $this->getDecimals($number);
        $table = $this->getConversionTable();
        $log = max(1, log($this->target->getRadix(), $this->source->getRadix()));

        if ($log > 1 && $pad = count($number) % $log) {
            $pad = count($number) + ($log - $pad);
            $number = array_pad($number, $pad * ($fractions ? +1: -1), 0);
        }

        $replacements = [[]];

        foreach (array_chunk($number, $log) as $chunk) {
            $replacements[] = $table[implode(':', $chunk)];
        }

        $result = call_user_func_array('array_merge', $replacements);

        while (($fractions ? end($result) : reset($result)) === 0) {
            unset($result[key($result)]);
        }

        return $this->getDigits($result);
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
        $max = $reduce ? $this->source->getRadix() : $this->target->getRadix();
        $min = $reduce ? $this->target->getRadix() : $this->source->getRadix();
        $size = (int) log($max, $min);
        $number = array_fill(0, $size, 0);
        $table = [];

        for ($i = 0; $i < $max; $i++) {
            if ($i > 0) {
                for ($j = $size - 1; $number[$j] == $min - 1; $j--) {
                    $number[$j] = 0;
                }
                $number[$j]++;
            }

            if ($reduce) {
                $table[$i] = $number;
            } else {
                $table[implode(':', $number)] = [$i];
            }
        }

        return $table;
    }
}
