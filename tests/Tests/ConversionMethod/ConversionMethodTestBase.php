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

    public function testCaseInsensitiveConversion()
    {
        $conv = $this->getConverter(16, 2);
        $this->assertSame(str_split('10101100101011011110'),
            $conv->convertNumber(str_split('ACadE')));
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
            ['2', '10', 16, 2],
            ['A09FF', '2404777', 16, 8],
            ['ABCDEF', '101010111100110111101111', 16, 2],
            ['FABCAB', 'FLF5B', 16, 32],
            ['FABCABABBA', '511373342342371', 27, 9],
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
