<?php

namespace Riimu\Kit\NumberConversion\Converter\Replace;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
trait ConversionTableBuilderTrait
{
    private $conversionTable;

    public function getConversionTable()
    {
        if (!isset($this->conversionTable)) {
            $this->conversionTable = $this->buildConversionTable();
        }

        return $this->conversionTable;
    }

    protected function buildConversionTable()
    {
        $reduce = $this->source->getRadix() > $this->target->getRadix();
        $max = $reduce ? $this->source : $this->target;
        $min = $reduce ? $this->target : $this->source;

        $minDigits = $min->getNumbers();
        $maxDigits = $max->getNumbers();
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
                $this->addItem($table, [$i], [$maxDigits[$i]], $next, $number);
            } else {
                $this->addItem($table, $next, $number, [$i], [$maxDigits[$i]]);
            }
        }

        return $table;
    }

    abstract protected function addItem(& $table, $sValues, $sDigits, $tValues, $tDigits);
}
