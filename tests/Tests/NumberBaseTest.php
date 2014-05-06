<?php

namespace Riimu\Kit\NumberConversion;

/**
 * Tests for NumberBase.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 */
class NumberBaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidBaseType ()
    {
        new NumberBase(true);
    }

    public function testCreateDefaultIntegerBase ()
    {
        $base = new NumberBase(18);
        $this->assertEquals(18, $base->getRadix());
        $this->assertEquals('G', $base->getDigit(16));
        $this->assertEquals(17, $base->getValue('H'));
    }

    public function testCreateBase64IntegerBase ()
    {
        $base = new NumberBase(64);
        $this->assertEquals('A', $base->getDigit(0));
        $this->assertEquals(62, $base->getValue('+'));
    }

    public function testCreateByteIntegerBase ()
    {
        $base = new NumberBase(256);
        $this->assertEquals("\x64", $base->getDigit(0x64));
        $this->assertEquals(032, $base->getValue("\032"));
    }

    public function testCreateLargeIntegerBase ()
    {
        $base = new NumberBase(512);
        $this->assertEquals("#306", $base->getDigit(306));
        $this->assertEquals(32, $base->getValue("#032"));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateWithTooSmallInteger ()
    {
        new NumberBase(1);
    }

    public function testCreateWithString ()
    {
        $base = new NumberBase('ABCDEF');
        $this->assertEquals(6, $base->getRadix());
        $this->assertEquals(4, $base->getValue('E'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBaseWithTooFewCharacters ()
    {
        new NumberBase('0');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBaseWithDuplicateCharacters ()
    {
        new NumberBase('00');
    }

    public function testCreateWithArray ()
    {
        $base = new NumberBase(['foo', 'bar']);
        $this->assertEquals(2, $base->getRadix());
        $this->assertEquals(0, $base->getValue('foo'));
        $this->assertEquals('bar', $base->getDigit(1));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBaseWithSingleNumber ()
    {
        new NumberBase([0]);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBaseWithDuplicateNumbers ()
    {
        new NumberBase([0, 0, 1]);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBaseWithMissingValues ()
    {
        new NumberBase([0 => 0, 2 => 1]);
    }

    public function testBaseWithNonScalarValues()
    {
        $zero = new \stdClass();
        $zero->n = 0;
        $one = new \stdClass();
        $one->n = 1;

        $base = new NumberBase([$zero, $one]);
        $this->assertSame($one, $base->getDigit(1));
        $this->assertSame(0, $base->getValue($zero));
        $this->assertSame([$zero, $one], $base->getDigitList());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGettingMissingDecimalValue ()
    {
        $base = new NumberBase(16);
        $base->getDigit(17);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGettingMissingCharacter ()
    {
        $base = new NumberBase(16);
        $base->getValue('G');
    }

    public function testCaseSensitivity()
    {
        $base = new NumberBase(36);
        $this->assertTrue($base->hasDigit('A'));
        $this->assertTrue($base->hasDigit('a'));
        $this->assertEquals(10, $base->getValue('A'));
        $this->assertEquals(10, $base->getValue('a'));

        $base = new NumberBase('aAB');
        $this->assertFalse($base->hasDigit('b'));
    }

    /**
     * @dataProvider getFindCommonRadixRootTestValues
     */
    public function testFindCommonRadixRoot ($a, $b, $common)
    {
        $aBase = new NumberBase($a);
        $bBase = new NumberBase($b);
        $this->assertEquals($common, $aBase->findCommonRadixRoot($bBase));
    }

    public function getFindCommonRadixRootTestValues ()
    {
        return [
            [4, 8, 2],
            [4, 16, 4],
        ];
    }

    public function testStaticBase()
    {
        $this->assertTrue((new NumberBase(32))->hasStaticLength());
        $this->assertTrue((new NumberBase(64))->hasStaticLength());
        $this->assertTrue((new NumberBase(128))->hasStaticLength());
        $this->assertTrue((new NumberBase(1000))->hasStaticLength());
        $this->assertTrue((new NumberBase('abcd'))->hasStaticLength());
        $this->assertTrue((new NumberBase(['aa', 'ab', 'bb', 'ba']))->hasStaticLength());
        $this->assertFalse((new NumberBase(['a', 'aa']))->hasStaticLength());
        $this->assertFalse((new NumberBase([1, 2]))->hasStaticLength());
    }
}
