<?php

namespace Riimu\Kit\NumberConversion\Method\Direct;

use Riimu\Kit\NumberConversion\Method\ConversionException;

/**
 * Provides checks to tell if integer overflow can occur during long division.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
trait IntegerConstrainedTrait
{
    /**
     * Throws a ConversionException if number bases can result in integer overflow
     * @throws PossibleOverflowException If there is possibility for integer overflow
     */
    public function verifyIntegerConstraint()
    {
        if ($this->isConstrained()) {
            throw new PossibleOverflowException("Possible integer overflow with given bases");
        }
    }

    /**
     * Tells if integer overflow can happen during the long division
     * @return boolean True if overflow can happen, false if not
     */
    public function isConstrained()
    {
        return $this->canOverflow(
            $this->source->getRadix(),
            $this->target->getRadix()
        );
    }

    /**
     * Calculates if integer overflow is possible.
     * @return boolean True if possible, false if not
     */
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

/**
 * Gets thrown when the conversion strategy fails due to 32 bit integer limit.
 */
class PossibleOverflowException extends ConversionException { }