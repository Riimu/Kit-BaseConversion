<?php

namespace Riimu\Kit\NumberConversion\ConversionMethod;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
trait IntegerConstrained
{
    public function isConstrained($source, $target)
    {
        $digits = ceil(log($target, $source));
        $limit = pow(2, 31) / ($source - 1);

        for ($i = 0, $tops = 0; $i < $digits; $i++) {
            $tops += pow($source, $i);

            if ($limit < $tops) {
                return true;
            }
        }

        return false;
    }
}
