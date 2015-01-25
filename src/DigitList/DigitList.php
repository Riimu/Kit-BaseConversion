<?php

namespace Riimu\Kit\BaseConversion\DigitList;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface DigitList extends \Countable
{
    public function hasStringConflict();
    public function isCaseSensitive();
    public function getDigits();
    public function getDigit($value);
    public function getValue($digit);
}
