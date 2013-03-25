<?php

namespace Tests\ConversionMethod;

use Riimu\Kit\NumberConversion\NumberBase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class ReplaceConversionTestBase extends ConversionMethodTestBase
{
    public function getNumberConversionData ()
    {
        return array_filter(parent::getNumberConversionData(), function ($data) {
            $a = new NumberBase($data[2]);
            $b = new NumberBase($data[3]);
            return $a->findCommonRadixRoot($b) !== false;
        });
    }

    /**
     * @dataProvider getFractionConversionData
     */
    public function testFractionConversion($input, $result, $source, $target)
    {
        $this->assertSame(str_split($result), $this->getConverter($source, $target)
            ->convertFractions(str_split($input)));
    }

    public function getFractionConversionData ()
    {
        return [
            ['2', '1', 4, 2],
            ['1', '01', 4, 2],
        ];
    }
}
