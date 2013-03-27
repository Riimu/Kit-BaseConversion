<?php

use Riimu\Kit\NumberConversion\BaseConverter;

/**
 * Tests for NumberConverter.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 */
class BaseConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicConversionFunctionality()
    {
        $converter = new BaseConverter(16, 2);
        $this->assertSame('101010111100110111101111', $converter->convert('ABCDEF'));
    }

    public function testConversionFallback ()
    {
        $converter = new BaseConverter(8, 10);
        $this->assertSame('73', $converter->convert('111'));
    }

    public function testFractionConversion()
    {
        $converter = new BaseConverter(10, 2);

        $converter->setPrecision(10);
        $this->assertEquals('11.0010001111', $converter->convert('3.14'));

        $converter->setPrecision(9);
        $this->assertEquals('11.001001000', $converter->convert('3.14'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testMissingNumberConversionMethod ()
    {
        $converter = new BaseConverter(2, 10);
        $converter->setNumberConverters(['Method\Replace\DirectReplaceConverter']);
        $converter->convert([1, 1, 1]);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testMissingFractionConversionMethod ()
    {
        $converter = new BaseConverter(2, 10);
        $converter->setFractionConverters(['Method\Replace\DirectReplaceConverter']);
        $converter->convert(['.', 1, 1, 1]);
    }

    public function testEmptyNumber()
    {
        $converter = new BaseConverter(8, 16);
        $this->assertSame('0', $converter->convert(''));
        $this->assertSame('-0', $converter->convert('-'));
        $this->assertSame('-0.0', $converter->convert('-.'));
        $this->assertSame('0.0', $converter->convert('.'));
        $this->assertSame(['0'], $converter->convert([]));
    }

    public function testSignedNumbers()
    {
        $converter = new BaseConverter(10, 2);
        $this->assertEquals('-1010111', $converter->convert('-87'));
        $this->assertEquals('+1010111', $converter->convert('+87'));
    }

    public function testFractionConversionByReplace()
    {
        $converter = new BaseConverter(27, 9);
        $this->assertEquals('22.7782135321061', $converter->convert('K.NH6CG2363'));
    }

    public function testConversionWithSameBase()
    {
        $converter = new BaseConverter(10, 10);
        $this->assertSame('42.42', $converter->convert('42.42'));
        $this->assertSame('0', $converter->convert('0'));
        $this->assertSame('0.0', $converter->convert('0.0'));
    }

    public function testConversionCharacterIgnorance()
    {
        $converter = new BaseConverter('.-', '01');
        $this->assertSame('11001', $converter->convert('--..-'));

        $converter = new BaseConverter('.+', '01');
        $this->assertSame('11001', $converter->convert('++..+'));
    }

    public function testConverterLoadingByFullName()
    {
        $converter = new BaseConverter(13, 23);
        $converter->setNumberConverters(['Riimu\Kit\NumberConversion\Method\Decimal\InternalConverter']);
        $this->assertSame('LDE2D', $converter->convert('1337331'));
    }
}
