<?php

namespace Riimu\Kit\NumberConversion\Converter\Replace;

use Riimu\Kit\NumberConversion\Converter\ConverterTestBase;
use Riimu\Kit\NumberConversion\NumberBase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class ReplaceTestBase extends ConverterTestBase
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
     * @expectedException Riimu\Kit\NumberConversion\Converter\ConversionException
     */
    public function testMissingCommonRoot()
    {
        $this->getConverter(7, 13)->convertInteger(['1']);
    }

    /**
     * @dataProvider getFractionConversionData
     */
    public function testFractionConversion($input, $result, $source, $target)
    {
        $this->assertSame(str_split($result), $this->getConverter($source, $target)
            ->convertFractions(str_split($input)));
        $this->assertSame(str_split($input), $this->getConverter($target, $source)
            ->convertFractions(str_split($result)));
    }

    public function getFractionConversionData ()
    {
        return [
            ['2', '1', 4, 2],
            ['1', '01', 4, 2],
            ['ABEEF', 'LFNF', 16, 32],
            ['302230323', 'CACEC', 4, 16],
            ['NH6CG2363', '7782135321061', 27, 9]
        ];
    }
}
