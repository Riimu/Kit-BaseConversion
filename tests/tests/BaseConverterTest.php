<?php

namespace Riimu\Kit\BaseConversion;

use PHPUnit\Framework\TestCase;
use Riimu\Kit\BaseConversion\DigitList\InvalidDigitException;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BaseConverterTest extends TestCase
{
    public function testStaticMethod()
    {
        $this->assertSame('101000110111001100110100', BaseConverter::baseConvert('A37334', 16, 2));
        $this->assertSame('-113863.683853', BaseConverter::baseConvert('-1BCC7.AF11', 16, 10));
        $this->assertSame('-113863.68', BaseConverter::baseConvert('-1BCC7.AF11', 16, 10, 2));
    }

    public function testCreatingWithReplaceBases()
    {
        $this->assertInstanceOf(
            BaseConverter::class,
            new BaseConverter(new NumberBase(8), new NumberBase(16))
        );
    }

    public function testCreatingWithMathBases()
    {
        $this->assertInstanceOf(
            BaseConverter::class,
            new BaseConverter(new NumberBase(10), new NumberBase(2))
        );
    }

    public function testCreatingWithStringBases()
    {
        $this->assertInstanceOf(
            BaseConverter::class,
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
        $this->assertSame('-1010111', $converter->convert('-87'));
        $this->assertSame('+1010111', $converter->convert('+87'));
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
        $this->assertSame('-22.7782135321061', (new BaseConverter(27, 9))->convert('-K.NH6CG2363'));
    }

    public function testInvalidDigits()
    {
        $this->assertFalse((new BaseConverter(2, 16))->convert('2'));
    }

    public function testInvalidDigitsInIntegerConversion()
    {
        $this->expectException(InvalidDigitException::class);
        (new BaseConverter(2, 16))->convertInteger(['2']);
    }

    public function testInvalidDigitsInFractionConversion()
    {
        $this->expectException(InvalidDigitException::class);
        (new BaseConverter(2, 16))->convertFractions(['2']);
    }
}
