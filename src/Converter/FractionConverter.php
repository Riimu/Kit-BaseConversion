<?php

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface FractionConverter
{
    public function convertFractions(array $number);
    public function setPrecision($precision);
}
