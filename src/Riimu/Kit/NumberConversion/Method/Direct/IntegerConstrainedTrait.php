<?php

namespace Riimu\Kit\NumberConversion\Method\Direct;

use Riimu\Kit\NumberConversion\Method\ConversionException;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
trait IntegerConstrainedTrait
{
    public function verifyIntegerConstraint()
    {
        if ($this->isConstrained()) {
            throw new PossibleOverflowException("Possible integer overflow with given bases");
        }
    }

    public function isConstrained()
    {
        return $this->canOverflow(
            $this->source->getRadix(),
            $this->target->getRadix()
        );
    }

    private function canOverflow($source, $target)
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

class PossibleOverflowException extends ConversionException { }