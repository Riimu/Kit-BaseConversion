<?php

namespace Riimu\Kit\BaseConversion;

use PHPUnit\Framework\TestCase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DecimalConverterTest extends TestCase
{
    public function testCreatingValidConverter()
    {
        $this->assertInstanceOf(
            DecimalConverter::class,
            new DecimalConverter(new NumberBase(2), new NumberBase(16))
        );
    }

    public function testConvertingHigherToLower()
    {
        $converter = new DecimalConverter(new NumberBase(16), new NumberBase(2));
        $this->assertSame(
            str_split('11000010011010010011111010'),
            $converter->convertInteger(str_split('309A4FA'))
        );
        $this->assertSame(
            str_split('001100001001101001001111101'),
            $converter->convertFractions(str_split('309A4FA'))
        );
    }

    public function testConvertingLowerToHigher()
    {
        $converter = new DecimalConverter(new NumberBase(2), new NumberBase(16));
        $this->assertSame(
            str_split('309A4FA'),
            $converter->convertInteger(str_split('11000010011010010011111010'))
        );
        $this->assertSame(
            str_split('309A4FA'),
            $converter->convertFractions(str_split('001100001001101001001111101'))
        );
    }

    public function testConvertingWithSameRadix()
    {
        $converter = new DecimalConverter(new NumberBase('0123'), new NumberBase('ABCD'));
        $this->assertSame(str_split('BDBBCACA'), $converter->convertInteger(str_split('013112020')));
        $this->assertSame(str_split('ABDBBCAC'), $converter->convertFractions(str_split('013112020')));
    }

    public function testConvertingLargeNumber()
    {
        $converter = new DecimalConverter(new NumberBase(16), new NumberBase(8));
        $this->assertSame(
            str_split('115047654244325677773'),
            $converter->convertInteger(str_split('1344FAC523577FFB'))
        );
    }

    public function testEmptyConversion()
    {
        $converter = new DecimalConverter(new NumberBase(16), new NumberBase(8));
        $this->assertSame(['0'], $converter->convertInteger([]));
        $this->assertSame(['0'], $converter->convertFractions([]));
    }

    public function testTypeCanonization()
    {
        $converter = new DecimalConverter(new NumberBase([0, 1, 2, 3]), new NumberBase([0, 1]));
        $this->assertSame([1, 1, 0, 0, 0], $converter->convertInteger(str_split('120')));
        $converter2 = new DecimalConverter(new NumberBase('AB'), new NumberBase([0, 1]));
        $this->assertSame([1, 0, 1], $converter2->convertInteger(str_split('BAB')));
    }

    public function testCaseSensitivity()
    {
        $converter = new DecimalConverter(new NumberBase(16), new NumberBase(2));
        $this->assertSame(
            str_split('11000010011010010011111010'),
            $converter->convertInteger(str_split('309A4FA'))
        );
        $this->assertSame(
            str_split('11000010011010010011111010'),
            $converter->convertInteger(str_split('309a4fa'))
        );

        $converter2 = new DecimalConverter(new NumberBase('aA'), new NumberBase(16));
        $this->assertSame(
            str_split('309A4FA'),
            $converter2->convertInteger(str_split('AAaaaaAaaAAaAaaAaaAAAAAaAa'))
        );
    }

    /**
     * @param string $input The input number
     * @param string $result The resulting number
     * @param int $source The source number base radix
     * @param int $target The target number base radix
     * @dataProvider getIntegerConversionData
     */
    public function testIntegerConversion($input, $result, $source, $target)
    {
        $input = is_array($input) ? $input : str_split($input);
        $result = is_array($result) ? $result : str_split($result);
        $source = new NumberBase($source);
        $target = new NumberBase($target);

        $this->assertSame($result, (new DecimalConverter($source, $target))->convertInteger($input));
        $this->assertSame($input, (new DecimalConverter($target, $source))->convertInteger($result));
    }

    public function getIntegerConversionData()
    {
        return [
            ['16778DA0', '2635706640', 16, 8],
            ['2413323433233422122', '2LIDNHIMBC', 5, 25],
            ['BA', 'DC', 'AB', 'CD'],
            ['0', '0', 10, 10],
            ['2', '10', 16, 2],
            ['A09FF', '2404777', 16, 8],
            ['ABCDEF', '101010111100110111101111', 16, 2],
            ['FABCAB', 'FLF5B', 16, 32],
            ['FABCABABBA', '511373342342371', 27, 9],
            [['#0777', '#0666', '#0555'], 'wmmor', 1024, 64],
        ];
    }

    /**
     * @param string $input The input number
     * @param string $result The resulting number
     * @param int $source The source number base radix
     * @param int $target The target number base radix
     * @dataProvider getFractionConversionData
     */
    public function testFractionConversion($input, $result, $source, $target)
    {
        $source = new NumberBase($source);
        $target = new NumberBase($target);

        $this->assertSame(
            str_split($result),
            (new DecimalConverter($source, $target))->convertFractions(str_split($input))
        );
        $this->assertSame(
            str_split($input),
            (new DecimalConverter($target, $source))->convertFractions(str_split($result))
        );
    }

    public function getFractionConversionData()
    {
        return [
            ['2', '1', 4, 2],
            ['1', '01', 4, 2],
            ['ABEEF', 'LFNF', 16, 32],
            ['302230323', 'CACEC', 4, 16],
            ['NH6CG2363', '7782135321061', 27, 9],
        ];
    }

    /**
     * @param string $input The input number
     * @param string $result The resulting number
     * @param int $source The source number base radix
     * @param int $target The target number base radix
     * @param int $precision The result number precision
     * @dataProvider getPrecisionConversionData
     */
    public function testConversionPrecision($input, $result, $source, $target, $precision)
    {
        $conv = new DecimalConverter(new NumberBase($source), new NumberBase($target));
        $conv->setPrecision($precision);
        $this->assertSame(str_split($result), $conv->convertFractions(str_split($input)));
    }

    public function getPrecisionConversionData()
    {
        return [
            ['1', '5', 2, 10, 0],
            ['2', '666', 3, 10, 3],
            ['1', '33', 3, 10, -1],
            ['75', '1', 10, 2, 1],
            ['14', '1075341217', 10, 8, 10],
            ['14', '001000111', 10, 2, 9],
            ['14', '0010001111', 10, 2, 10],
            ['14', '0010', 10, 2, 4],
            ['42', '01101011', 10, 2, -1],
        ];
    }
}
