<?php

namespace Riimu\Kit\NumberConversion\Method\Decimal;

/**
 * Provides slow decimal conversion using arbitrary precision implementation.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class InternalConverter extends AbstractDecimalConverter
{
    public function isSupported()
    {
        return true;
    }

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
        if (strlen($a) < 10 && strlen($b) < 10) {
            return (string) ($a + $b);
        } elseif ($a == '0') {
            return $b;
        } elseif ($b == '0') {
            return $a;
        }

        $a = $this->splitFromRight($a, 9);
        $b = $this->splitFromRight($b, 9);
        $mask = 1000000000;

        if (count($a) > count($b)) {
            $temp = $a;
            $a = $b;
            $b = $temp;
        }

        $overflow = 0;
        $aLength = count($a);
        $bLength = count($b);

        for ($i = 1; $i <= $aLength; $i++) {
            $chunk = $a[$aLength - $i] + $b[$bLength - $i] + $overflow;
            $overflow = (int) ($chunk / $mask);
            $b[$bLength - $i] = sprintf('%09s', $chunk % $mask);
        }

        if ($overflow > 0 && $bLength > $aLength) {
            do {
                $chunk = $b[$bLength - $i] + $overflow;
                $overflow = (int) ($chunk / $mask);
                $b[$bLength - $i] = sprintf('%09s', $chunk % $mask);
            } while ($overflow && $i++ < $bLength);
        } elseif ($bLength > $aLength) {
            return implode('', $b);
        }

        return $overflow > 0
            ? $overflow . implode('', $b) : ltrim(implode('', $b), '0');
    }

    protected function mul($a, $b)
    {
        if (strlen($a) + strlen($b) < 10) {
            return (string) ($a * $b);
        } elseif ($a == '1') {
            return $b;
        } elseif ($b == '1') {
            return $a;
        } elseif ($a == '0' || $b == '0') {
            return '0';
        }

        $a = array_reverse($this->splitFromRight($a, 8));
        $mask = 100000000;
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
        if (strlen($a) <= 5 && $b <= 32 && is_int($pow = pow($a, $b))) {
            return (string) $pow;
        } elseif ($b == 0 || $a == '1') {
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
        if (strlen($a) < 10 && strlen($b) < 10) {
            return [(string)(int) ($a / $b), (string) ($a % $b)];
        } elseif ($this->cmp($a, $b) < 0) {
            return ['0', $a];
        }

        $zeroIt = false;
        $result = '';
        $temp = substr($a, 0, strlen($b));
        $pos = strlen($b);
        $intDiv = $this->cmp($b, '100000000') < 0;

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

            if ($intDiv) {
                $count = (int) ($temp / $b);
                $temp = $temp % $b;
            } else {
                $temp = $this->subFrom($temp, $b);
                for ($count = 1; $this->cmp($temp, $b) >= 0; $count++) {
                    $temp = $this->subFrom($temp, $b);
                }
            }

            $result .= $count;
            $zeroIt = false;
        }

        return [$result, $temp];
    }

    /**
     * Substracts a smaller positive integer from larger positive integer.
     * @param string $a The number to subtract from
     * @param string $b The number to subtract
     * @return string The difference as a string
     */
    private function subFrom($a, $b)
    {
        if (strlen($a) < 10 && strlen($b) < 10) {
            return (string) ($a - $b);
        }

        $a = $this->splitFromRight($a, 9);
        $b = $this->splitFromRight($b, 9);
        $mask = 1000000000;

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

    /**
     * Splits string using right justification.
     * @param string $string String to split
     * @param integer $split The number of characters in each chunk
     * @return array The string plit into chunks
     */
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
