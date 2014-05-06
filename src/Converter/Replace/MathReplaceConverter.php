<?php

namespace Riimu\Kit\NumberConversion\Converter\Replace;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class MathReplaceConverter extends AbstractReplaceConverter
{
    protected function replace(array $number, $fractions = false)
    {
        $source = $this->source->getRadix();
        $target = $this->target->getRadix();
        $number = $this->getValues($number);

        if ($source < $target) {
            $log = log($target, $source);
            $result = [];

            if ($fractions && ($pad = count($number) % $log)) {
                $number = array_pad($number, count($number) + ($log - $pad), 0);
            }

            foreach (array_chunk(array_reverse($number), $log) as $chunk) {
                $value = 0;

                foreach ($chunk as $pow => $dec) {
                    $value += $dec * pow($source, $pow);
                }

                $result[] = $value;
            }
        } else {
            $log = log($source, $target);
            $result = [];

            foreach (array_reverse($number) as $dec) {
                for ($i = 0; $i < $log; $i++) {
                    $result[] = $dec % $target;
                    $dec = (int) ($dec / $target);
                }
            }
        }

        while (($fractions ? reset($result) : end($result)) === 0) {
            unset($result[key($result)]);
        }

        return $this->getDigits(array_reverse($result));
    }
}
