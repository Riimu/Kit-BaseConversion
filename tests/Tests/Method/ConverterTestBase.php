<?php

namespace Tests\Method;

use Riimu\Kit\NumberConversion\NumberBase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class ConverterTestBase extends \PHPUnit_Framework_TestCase
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
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidInput()
    {
        $conv = $this->getConverter(4, 16);
        $conv->convertNumber([-1]);
    }

    public function testLooseValueTypeComparison()
    {
        $conv = $this->getConverter(2, 16);
        $this->assertSame(['E'], $conv->convertNumber([true, '1', 1, false]));
    }

    /**
     * @dataProvider getNumberConversionData
     */
    public function testNumberConversion($input, $result, $source, $target)
    {
        $input = is_array($input) ? $input : str_split($input);
        $result = is_array($result) ? $result : str_split($result);

        $this->assertSame($result, $this->getConverter($source, $target)
            ->convertNumber($input));
        $this->assertSame($input, $this->getConverter($target, $source)
            ->convertNumber($result));
    }

    public function getNumberConversionData ()
    {
        return [
            ['16778DA0', '2635706640', 16, 8],
            ['2413323433233422122', '2LIDNHIMBC', 5, 25],
            ['111', '73', 8, 10],
            ['111', '7', 2, 10],
            ['BA', 'DC', 'AB', 'CD'],
            ['11', '3', 2, 10],
            ['0', '0', 10, 10],
            ['2', '10', 16, 2],
            ['A09FF', '2404777', 16, 8],
            ['ABCDEF', '101010111100110111101111', 16, 2],
            ['FABCAB', 'FLF5B', 16, 32],
            ['FABCABABBA', '511373342342371', 27, 9],
            [sprintf('%c%c%c%c%c%c', 245, 69, 123, 99, 59, 117), 'B7627A314C886', 256, 13],
            [['#0777', '#0666', '#0555'], 'wmmor', 1024, 64],
            [[['', ''], ['', '', '']], [true, true, false], [[''], ['', ''], ['', '', ''], new \stdClass()], [false, true]],
            ['2919739656537', '101010011111001110000010111000100101011001', 10, 2],
            ['A09GH0076AAB49DEF', 'IMOI1A8HM60KPH9', '0123456789ABCDEFGH', '0123456789ABCDEFGHIJKLMNOP'],
            ['1337331', 'LDE2D', 13, 23],
            [['a', 'aa', 'aaa'], '1B', ['0', 'a', 'aa', 'aaa'], 16],
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
