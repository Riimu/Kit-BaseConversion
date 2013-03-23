<?php

namespace Riimu\Kit\NumberConversion\DecimalConverter;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class InternalConverter extends DecimalConverter
{
    protected function init($number)
    {
        return (string) $number;
    }

    protected function val($number)
    {
        return (string) $number;
    }

    protected function add($a, $b)
    {
        if ($a == '0') {
            return $b;
        } elseif ($b == '0') {
            return $a;
        }

        $a = $this->splitFromRight($a, 9);
        $b = $this->splitFromRight($b, 9);
        $mask = pow(10, 9);

        $a = array_pad($a, -count($b), 0);

        $result = '';
        $overflow = 0;

        while (($left = array_pop($a)) !== null) {
            $chunk = $left + array_pop($b) + $overflow;
            $overflow = (int) ($chunk / $mask);
            $result = sprintf('%09s', $chunk % $mask) . $result;
        }

        return ltrim($overflow . $result, '0');
    }

    protected function mul($a, $b)
    {
        if ($a == '1') {
            return $b;
        } elseif ($b == '1') {
            return $a;
        } elseif ($a == '0' || $b == '0') {
            return '0';
        }

        $a = array_reverse($this->splitFromRight($a, 8));
        $mask = pow(10, 8);
        $adds = [];

        foreach(str_split(strrev($b)) as $zeros => $multiplier) {
            if ($multiplier == 0) {
                continue;
            }

            $add = str_repeat('0', $zeros);
            $overflow = 0;

            foreach ($a as $chunk) {
                $chunk = $chunk * $multiplier + $overflow;
                $overflow = (int) ($chunk / $mask);
                $add = sprintf('%08s', $chunk % $mask) . $add;
            }

            $adds[] = ltrim($overflow . $add, '0');
        }

        $result = array_shift($adds);

        foreach ($adds as $value) {
            $result = $this->add($result, $value);
        }

        return $result;
    }

    protected function pow($a, $b)
    {
        if ($b == 0 || $a == '1') {
            return '1';
        } elseif ($b == 1) {
            return $a;
        }

        $pows = [$a];

        while ($b >= (1 << count($pows))) {
            $pows[] = $this->mul(end($pows), end($pows));
        }

        $result = '1';

        foreach ($pows as $pow => $value) {
            if ($b & (1 << $pow)) {
                $result = $this->mul($result, $value);
            }
        }

        return $result;
    }

    protected function div($a, $b)
    {
        if ($this->cmp($a, $b) < 0) {
            return ['0', $a];
        }

        $zeroIt = false;
        $result = '';
        $temp = substr($a, 0, strlen($b));
        $pos = strlen($b);

        while (true) {
            while ($this->cmp($temp, $b) < 0) {
                if ($zeroIt) {
                    $result .= '0';
                }
                if (!isset($a[$pos])) {
                    break 2;
                }

                $temp = ($temp == '0' ? '' : $temp) . $a[$pos++];
                $zeroIt = true;
            }

            $temp = $this->subFrom($temp, $b);
            for ($count = 1; $this->cmp($temp, $b) >= 0; $count++) {
                $temp = $this->subFrom($temp, $b);
            }

            $result .= $count;
            $zeroIt = false;
        }

        return [$result, $temp];
    }

    private function subFrom($a, $b)
    {
        $a = $this->splitFromRight($a, 9);
        $b = $this->splitFromRight($b, 9);
        $mask = pow(10, 9);

        foreach (array_reverse($a, true) as $key => $chunk) {
            $a[$key] = $chunk - array_pop($b);
        }

        $result = '';

        foreach (array_reverse(array_keys($a)) as $key) {
            if ($a[$key] < 0) {
                $a[$key - 1] -= 1;
                $a[$key] = $a[$key] + $mask;
            }

            $result = sprintf('%09s', $a[$key]) . $result;
        }

        return ltrim($result, '0') ?: '0';
    }

    protected function cmp($a, $b)
    {
        if ($diff = strlen($a) - strlen($b)) {
            return $diff > 0 ? 1 : -1;
        }

        return strcmp($a, $b);
    }

    private function splitFromRight($string, $split)
    {
        if (strlen($string) <= $split) {
            return [$string];
        } elseif ($pos = strlen($string) % $split) {
            $first = substr($string, 0, $pos);
            $rest = str_split(substr($string, $pos), $split);
            return array_merge([$first], $rest);
        }

        return str_split($string, $split);
    }
}
