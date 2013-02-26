<?php

namespace Rose\NumberConversion;

/**
 * Tests for NumberConverter.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 */
class BaseConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testStringConversion ()
    {
        $converter = new BaseConverter(new NumberBase(16), new NumberBase(2));
        $this->assertEquals('101010111100110111101111', $converter->convertString('ABCDEF'));
    }

    public function testReplaceConversion ()
    {
        $converter = new BaseConverter(new NumberBase(5), new NumberBase(25));
        $backwards = new BaseConverter(new NumberBase(25), new NumberBase(5));
        $start = str_split('2413323433233422122');
        $this->assertEquals($start, $backwards->convertByReplace($converter->convertByReplace($start)));
    }

    public function testDirectConversion ()
    {
        $converter = new BaseConverter(new NumberBase(16), new NumberBase(2));
        $this->assertEquals('101010111100110111101111',
            implode('', $converter->convertDirectly(str_split('ABCDEF'))));
    }

    public function testCustomConversion ()
    {
        $this->assertEquals('101010111100110111101111',
            BaseConverter::customConvert('ABCDEF', '0123456789ABCDEF', '01'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testDecimalConversionError ()
    {
        $converter = new BaseConverter(new NumberBase(16), new NumberBase(2));
        $converter->convertFromDecimal('asdf');
    }

    public function testCustomConversionErrors ()
    {
        $this->assertFalse(BaseConverter::customConvert('A', '01', '012'));
        $this->assertFalse(BaseConverter::customConvert('1', '01', '0'));
    }

    public function testCustomConversionStringDetected ()
    {
        $this->assertEquals(str_split('101010111100110111101111'),
            BaseConverter::customConvert(str_split('ABCDEF'), str_split('0123456789ABCDEF'), '01'));
    }

    /**
     * @dataProvider getAlgorithmTests
     */
    public function testCorrectAlgorithms ($source, $target, $number, $expected)
    {
        $converter = new BaseConverter(new NumberBase($source), new NumberBase($target));

        $this->assertEquals($expected, $converter->convertString($number));
        $this->assertEquals($expected, implode('', $converter->convertDirectly(str_split($number))));
        $this->assertEquals($expected, BaseConverter::customConvert($number, $source, $target));
    }

    public function getAlgorithmTests ()
    {
        return array(
            array('0123456789', '01', '2919739656537', '101010011111001110000010111000100101011001'),
            array('0123456789ABCDEFGH', '0123456789ABCDEFGHIJKLMNOP', 'A09GH0076AAB49DEF', 'IMOI1A8HM60KPH9'),
        );
    }
}
