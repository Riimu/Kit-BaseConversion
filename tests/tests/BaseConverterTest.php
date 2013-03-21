<?php

use Riimu\Kit\NumberConversion\BaseConverter;
use Riimu\Kit\NumberConversion\NumberBase;

/**
 * Tests for NumberConverter.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 */
class BaseConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultReplaceConversion ()
    {
        $converter = new BaseConverter(16, 2);
        $this->assertEquals('101010111100110111101111', $converter->convert('ABCDEF'));
    }

    public function testDecimalConvertFallback ()
    {
        $converter = new BaseConverter(8, 10);
        $this->assertEquals('73', $converter->convert('111'));
    }

    public function testDirectConvertFallback ()
    {
        $converter = new BaseConverter(2, 10);
        $converter->setDecimalConverter(null);
        $this->assertEquals('7', $converter->convert('111'));
    }

    public function testReplaceConversion ()
    {
        $converter = new BaseConverter(5, 25);
        $backwards = new BaseConverter(25, 5);
        $start = str_split('2413323433233422122');
        $this->assertEquals($start, $backwards->convertByReplace($converter->convertByReplace($start)));
    }

    public function testConvertViaCommonRoot ()
    {
        $converter = new BaseConverter(16, 8);
        $this->assertEquals(str_split('2635706640'),
            $converter->convertViaCommonRoot(str_split('16778DA0')));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testDecimalConversionException ()
    {
        $converter = new BaseConverter(2, 10);
        $converter->setDecimalConverter(null);
        $converter->convertViaDecimal([1, 1, 1]);
    }

    public function testDirectConversion ()
    {
        $converter = new BaseConverter(16, 2);
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
        $converter = new BaseConverter(10, 15);
        $converter->convertViaCommonRoot(str_split('123'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidCharacterInReplaceConversion ()
    {
        $converter = new BaseConverter(2, 4);
        $converter->convertByReplace(['2']);
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
        $reverse = new BaseConverter($targetBase, $sourceBase);
        $number = str_split($number);
        $expected = str_split($expected);

        if ($sourceBase->findCommonRadixRoot($targetBase) !== false) {
            $this->assertEquals($expected, $converter->convertViaCommonRoot($number));
            $this->assertEquals($number, $reverse->convertViaCommonRoot($expected));
        }

        $this->assertEquals($expected, $converter->convertViaDecimal($number));
        $this->assertEquals($number, $reverse->convertViaDecimal($expected));

        $this->assertEquals($expected, $converter->convertDirectly($number));
        $this->assertEquals($number, $reverse->convertDirectly($expected));

        $this->assertEquals($expected, BaseConverter::customConvert($number, $source, $target));
        $this->assertEquals($number, BaseConverter::customConvert($expected, $target, $source));
    }

    public function getAlgorithmTests ()
    {
        return [
            ['0123456789', '01', '2919739656537', '101010011111001110000010111000100101011001'],
            ['0123456789ABCDEFGH', '0123456789ABCDEFGHIJKLMNOP', 'A09GH0076AAB49DEF', 'IMOI1A8HM60KPH9'],
            ['0123456789ABCDEF', '01234567', 'A09FF', '2404777'],
        ];
    }

    public function testNegativeNumbers()
    {
        $converter = new BaseConverter(10, 2);
        $this->assertEquals('-1010111', $converter->convert('-87'));
    }

    public function testFractionConversion()
    {
        $converter = new BaseConverter(10, 2);
        $decimal = $converter->getDecimalConverter();

        if ($decimal === null) {
            $this->markTestSkipped('No decimal conversion library available');
        }

        $decimal->setDefaultPrecision(10);
        $this->assertEquals('11.0010001111', $converter->convert('3.14'));
    }

    public function testFractionConversionByReplace()
    {
        $converter = new BaseConverter(27, 9);
        $this->assertEquals('22.7782135321061', $converter->convert('K.NH6CG2363'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMissingFractionConversion()
    {
        $converter = new BaseConverter(10, 2);
        $converter->setDecimalConverter(null);
        $converter->convert('3.14');
    }

    public function testEnsureReplaceConversionOnSameBase()
    {
        $converter = $this->getMock('Riimu\Kit\NumberConversion\BaseConverter', ['convertByReplace'], [10, 10]);
        $converter->expects($this->exactly(2))->method('convertByReplace')
            ->with($this->equalTo(['4', '2']))
            ->will($this->returnValue(['4', '2']));
        $this->assertEquals('42.42', $converter->convert('42.42'));
    }

    public function testReplacementConversionWithSameBase()
    {
        $converter = new BaseConverter(10, 10);
        $this->assertSame('42.42', $converter->convert('42.42'));
        $this->assertSame('0', $converter->convert('0'));
        $this->assertSame('0.0', $converter->convert('0.0'));
    }
}
