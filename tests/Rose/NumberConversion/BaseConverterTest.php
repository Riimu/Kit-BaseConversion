<?php

namespace Rose\NumberConversion;

/**
 * Tests for NumberConverter.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 */
class BaseConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultReplaceConversion ()
    {
        $converter = new BaseConverter(new NumberBase(16), new NumberBase(2));
        $this->assertEquals('101010111100110111101111', $converter->convertString('ABCDEF'));
    }
    
    public function testDecimalConvertFallback ()
    {
        $converter = new BaseConverter(new NumberBase(8), new NumberBase(10));
        $this->assertEquals('73', $converter->convertString('111'));
    }
    
    public function testDirectConvertFallback ()
    {
        $converter = new BaseConverter(new NumberBase(2), new NumberBase(10));
        $converter->setDecimalConverter(null);
        $this->assertEquals('7', $converter->convertString('111'));
    }

    public function testReplaceConversion ()
    {
        $converter = new BaseConverter(new NumberBase(5), new NumberBase(25));
        $backwards = new BaseConverter(new NumberBase(25), new NumberBase(5));
        $start = str_split('2413323433233422122');
        $this->assertEquals($start, $backwards->convertByReplace($converter->convertByReplace($start)));
    }
    
    public function testConvertViaCommonRoot ()
    {
        $converter = new BaseConverter(new NumberBase(16), new NumberBase(8));
        $this->assertEquals(str_split('2635706640'),
            $converter->convertViaCommonRoot(str_split('16778DA0')));
    }
    
    /**
     * @expectedException RuntimeException
     */
    public function testDecimalConversionException ()
    {
        $converter = new BaseConverter(new NumberBase(2), new NumberBase(10));
        $converter->setDecimalConverter(null);
        $converter->convertViaDecimal(array(1, 1, 1));
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
    public function testMissingCommonRoot ()
    {
        $converter = new BaseConverter(new NumberBase(10), new NumberBase(15));
        $converter->convertViaCommonRoot(str_split('123'));
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidCharacterInReplaceConversion ()
    {
        $converter = new BaseConverter(new NumberBase(2), new NumberBase(4));
        $converter->convertByReplace(array('2'));
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
        $sourceBase = new NumberBase($source);
        $targetBase = new NumberBase($target);
        $converter = new BaseConverter($sourceBase, $targetBase);
        $number = str_split($number);
        $expected = str_split($expected);

        if ($sourceBase->findCommonRadixRoot($targetBase) !== false) {
            $this->assertEquals($expected, $converter->convertViaCommonRoot($number));
        }
        $this->assertEquals($expected, $converter->convertViaDecimal($number));
        $this->assertEquals($expected, $converter->convertDirectly($number));
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
