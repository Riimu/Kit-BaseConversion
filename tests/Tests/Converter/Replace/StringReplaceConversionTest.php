<?php

namespace Riimu\Kit\NumberConversion\Converter\Replace;

use Riimu\Kit\NumberConversion\NumberBase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class StringReplaceConversionTest extends ReplaceTestBase
{
    protected $className = 'Riimu\Kit\NumberConversion\Converter\Replace\StringReplaceConverter';

    public function getNumberConversionData ()
    {
        return array_filter(parent::getNumberConversionData(), function ($data) {
            $a = new NumberBase($data[2]);
            $b = new NumberBase($data[3]);
            return !$a->hasStringConflict() && !$b->hasStringConflict();
        });
    }

    public function getFractionConversionData ()
    {
        return array_filter(parent::getFractionConversionData(), function ($data) {
            $a = new NumberBase($data[2]);
            $b = new NumberBase($data[3]);
            return !$a->hasStringConflict() && !$b->hasStringConflict();
        });
    }

    /**
     * @expectedException Riimu\Kit\NumberConversion\Converter\ConversionException
     */
    public function testNonStaticNumberBase()
    {
        $this->getConverter(['a', 'aa'], 4)->convertInteger(['a']);
    }
}
