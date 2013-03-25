<?php

namespace Tests\ConversionMethod;

use Riimu\Kit\NumberConversion\NumberBase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class ConversionMethodTestBase extends \PHPUnit_Framework_TestCase
{
    protected $className;

    public function testEmptyConversion()
    {
        $conv = $this->getConverter(4, 16);
        $this->assertSame(['0'], $conv->convertNumber([]));
    }

    /**
     * @dataProvider getNumberConversionData
     */
    public function testNumberConversion($input, $result, $source, $target)
    {
        $this->assertSame(str_split($result), $this->getConverter($source, $target)
            ->convertNumber(str_split($input)));
        $this->assertSame(str_split($input), $this->getConverter($target, $source)
            ->convertNumber(str_split($result)));
    }

    public function getNumberConversionData ()
    {
        return [
            ['BA', 'DC', 'AB', 'CD'],
            ['11', '3', 2, 10],
            ['0', '0', 10, 10],
            ['A09FF', '2404777', 16, 8],
            ['ABCDEF', '101010111100110111101111', 16, 2],
        ];
    }

    protected function getConverter($source, $target)
    {
        return new $this->className(
            new NumberBase($source),
            new NumberBase($target)
        );
    }
}
