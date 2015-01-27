<?php

namespace Riimu\Kit\BaseConversion;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BaseConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testStaticMethod()
    {
        $this->assertSame('101000110111001100110100', BaseConverter::baseConvert('A37334', 16, 2));
    }

    public function testCreatingWithReplaceBases()
    {
        $this->assertInstanceOf(
            'Riimu\Kit\BaseConversion\BaseConverter',
            new BaseConverter(new NumberBase(8), new NumberBase(16))
        );
    }

    public function testCreatingWithMathBases()
    {
        $this->assertInstanceOf(
            'Riimu\Kit\BaseConversion\BaseConverter',
            new BaseConverter(new NumberBase(10), new NumberBase(2))
        );
    }

    public function testCreatingWithStringBases()
    {
        $this->assertInstanceOf(
            'Riimu\Kit\BaseConversion\BaseConverter',
            new BaseConverter(10, 2)
        );
    }

    public function testIntegerConversion()
    {
        $this->assertSame(
            str_split('101010111100110111101111'),
            (new BaseConverter(16, 2))->convertInteger(str_split('ABCDEF'))
        );
        $this->assertSame(
            str_split('11259375'),
            (new BaseConverter(2, 10))->convertInteger(str_split('101010111100110111101111'))
        );
    }

    public function testFractionConversion()
    {
        $converter = new BaseConverter(10, 2);
        $converter->setPrecision(10);
        $this->assertSame(str_split('0010001111'), $converter->convertFractions(str_split('14')));
        $converter->setPrecision(9);
        $this->assertSame(str_split('001000111'), $converter->convertFractions(str_split('14')));
    }

    public function testEmptyStringConversions()
    {
        $converter = new BaseConverter(8, 16);
        $this->assertSame('0', $converter->convert(''));
        $this->assertSame('-0', $converter->convert('-'));
        $this->assertSame('-0.0', $converter->convert('-.'));
        $this->assertSame('0.0', $converter->convert('.'));
        $this->assertSame(['0'], $converter->convertInteger([]));
        $this->assertSame(['0'], $converter->convertFractions([]));
    }

    public function testSignedNumbers()
    {
        $converter = new BaseConverter(10, 2);
        $this->assertEquals('-1010111', $converter->convert('-87'));
        $this->assertEquals('+1010111', $converter->convert('+87'));
    }

    public function testConversionWithSameBase()
    {
        $converter = new BaseConverter(10, 10);
        $this->assertSame('42.42', $converter->convert('42.42'));
        $this->assertSame('0', $converter->convert('0'));
        $this->assertSame('0.0', $converter->convert('0.0'));
    }

    public function testFractionConversionByReplace()
    {
        $this->assertEquals('-22.7782135321061', (new BaseConverter(27, 9))->convert('-K.NH6CG2363'));
    }

    public function testInvalidDigits()
    {
        $this->assertFalse((new BaseConverter(2, 16))->convert('2'));
    }

    public function testInvalidDigitsInIntegerConversion()
    {
        $this->setExpectedException('Riimu\Kit\BaseConversion\InvalidDigitException');
        (new BaseConverter(2, 16))->convertInteger(['2']);
    }

    public function testInvalidDigitsInFractionConversion()
    {
        $this->setExpectedException('Riimu\Kit\BaseConversion\InvalidDigitException');
        (new BaseConverter(2, 16))->convertFractions(['2']);
    }
}
