<?php

namespace Riimu\Kit\BaseConversion;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class NumberBaseTest extends \PHPUnit_Framework_TestCase
{
    public function testInvalidBaseType()
    {
        $this->setExpectedException('\InvalidArgumentException');
        new NumberBase(true);
    }

    public function testCreateDefaultIntegerBase()
    {
        $base = new NumberBase(18);
        $this->assertSame(18, $base->getRadix());
        $this->assertSame('G', $base->getDigit(16));
        $this->assertSame(17, $base->getValue('H'));
    }

    public function testCreateBase64IntegerBase()
    {
        $base = new NumberBase(64);
        $this->assertSame('A', $base->getDigit(0));
        $this->assertSame(62, $base->getValue('+'));
    }

    public function testCreateByteIntegerBase()
    {
        $base = new NumberBase(256);
        $this->assertSame("\x64", $base->getDigit(0x64));
        $this->assertSame(032, $base->getValue("\032"));
    }

    public function testCreateLargeIntegerBase()
    {
        $base = new NumberBase(512);
        $this->assertSame('#306', $base->getDigit(306));
        $this->assertSame(32, $base->getValue('#032'));
    }

    public function testCreateWithTooSmallInteger()
    {
        $this->setExpectedException('\InvalidArgumentException');
        new NumberBase(1);
    }

    public function testCreateWithString()
    {
        $base = new NumberBase('ABCDEF');
        $this->assertSame(6, $base->getRadix());
        $this->assertSame(4, $base->getValue('E'));
    }

    public function testBaseWithTooFewCharacters()
    {
        $this->setExpectedException('\InvalidArgumentException');
        new NumberBase('0');
    }

    public function testBaseWithDuplicateCharacters()
    {
        $this->setExpectedException('\InvalidArgumentException');
        new NumberBase('00');
    }

    public function testCreateWithArray()
    {
        $base = new NumberBase(['foo', 'bar']);
        $this->assertSame(2, $base->getRadix());
        $this->assertSame(0, $base->getValue('foo'));
        $this->assertSame('bar', $base->getDigit(1));
    }

    public function testBaseWithSingleNumber()
    {
        $this->setExpectedException('\InvalidArgumentException');
        new NumberBase([0]);
    }

    public function testBaseWithDuplicateNumbers()
    {
        $this->setExpectedException('\InvalidArgumentException');
        new NumberBase([0, 0, 1]);
    }

    public function testBaseWithMissingValues()
    {
        $this->setExpectedException('\InvalidArgumentException');
        new NumberBase([0 => 0, 2 => 1]);
    }

    public function testNonScalarDigits()
    {
        $zero = (object) ['n' => 0];
        $one = (object) ['n' => 1];

        $base = new NumberBase([$zero, $one]);
        $this->assertSame($one, $base->getDigit(1));
        $this->assertSame(0, $base->getValue($zero));
        $this->assertSame([$zero, $one], $base->getDigitList());
    }

    public function testDuplicateNonScalarDigits()
    {
        $zeroA = (object) ['n' => 0];
        $zeroB = (object) ['n' => 0];

        $this->assertNotSame(spl_object_hash($zeroA), spl_object_hash($zeroB));

        $this->setExpectedException('InvalidArgumentException');
        new NumberBase([$zeroA, $zeroB]);
    }

    public function testInvalidNonScalarDigit()
    {
        $zero = (object) ['n' => 0];
        $one = (object) ['n' => 1];
        $two = (object) ['n' => 2];

        $base = new NumberBase([$zero, $one]);

        $this->setExpectedException('Riimu\Kit\BaseConversion\DigitList\InvalidDigitException');
        $base->getValue($two);
    }

    public function testGettingMissingDecimalValue()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $base = new NumberBase(16);
        $base->getDigit(17);
    }

    public function testGettingMissingCharacter()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $base = new NumberBase(16);
        $base->getValue('G');
    }

    public function testCaseSensitivity()
    {
        $base = new NumberBase(36);
        $this->assertTrue($base->hasDigit('A'));
        $this->assertTrue($base->hasDigit('a'));
        $this->assertSame(10, $base->getValue('A'));
        $this->assertSame(10, $base->getValue('a'));

        $base = new NumberBase('aAB');
        $this->assertFalse($base->hasDigit('b'));
    }

    /**
     * @dataProvider getFindCommonRadixRootTestValues
     */
    public function testFindCommonRadixRoot($a, $b, $common)
    {
        $aBase = new NumberBase($a);
        $bBase = new NumberBase($b);
        $this->assertSame($common, $aBase->findCommonRadixRoot($bBase));
    }

    public function getFindCommonRadixRootTestValues()
    {
        return [
            [4, 8, 2],
            [4, 16, 4],
        ];
    }

    public function testStringConflict()
    {
        $this->assertFalse((new NumberBase(32))->hasStringConflict());
        $this->assertFalse((new NumberBase(64))->hasStringConflict());
        $this->assertFalse((new NumberBase(128))->hasStringConflict());
        $this->assertFalse((new NumberBase(1000))->hasStringConflict());
        $this->assertFalse((new NumberBase('abcd'))->hasStringConflict());
        $this->assertFalse((new NumberBase(['aa', 'ab', 'bb', 'ba']))->hasStringConflict());
        $this->assertTrue((new NumberBase(['a', 'aa']))->hasStringConflict());
        $this->assertTrue((new NumberBase(['a', 'ba']))->hasStringConflict());
        $this->assertFalse((new NumberBase([1, 2]))->hasStringConflict());
        $this->assertTrue((new NumberBase([1, 11]))->hasStringConflict());
    }

    public function testIsCaseSensitive()
    {
        $this->assertFalse((new NumberBase('ab'))->isCaseSensitive());
        $this->assertFalse((new NumberBase(['a', 'b']))->isCaseSensitive());
        $this->assertTrue((new NumberBase('aA'))->isCaseSensitive());
        $this->assertTrue((new NumberBase(['a', 'A']))->isCaseSensitive());
    }

    public function testStringSplitting()
    {
        $this->assertSame(['0'], (new NumberBase('01'))->splitString(''));
        $this->assertSame(['b', 'a', 'c', 'a', 'D'], (new NumberBase('abcD'))->splitString('BaCad'));
        $this->assertSame(
            ['ba', 'C', 'ab', 'ba', 'aca', 'ab'],
            (new NumberBase(['C', 'ba', 'ab', 'aca']))->splitString('baCabbaacaab')
        );
        $this->assertSame(
            [0, 1, 0, 1, 1, 0],
            (new NumberBase([0, 1]))->splitString('010110')
        );
    }

    public function testUnsupportedSplitting()
    {
        $this->setExpectedException('\RuntimeException');
        (new NumberBase(['a', 'aa']))->splitString('aaa');
    }

    public function testMissingDigits()
    {
        $this->setExpectedException('Riimu\Kit\BaseConversion\DigitList\InvalidDigitException');
        (new NumberBase('01'))->splitString('2');
    }

    public function testConflictingSplit()
    {
        $this->assertSame(['0100', '0100'], (new NumberBase(['0100', '10001']))->splitString('01000100'));
    }

    public function testIntegerBaseCaseSensitivity()
    {
        $this->assertFalse((new NumberBase(97))->isCaseSensitive());
        $this->assertTrue((new NumberBase(98))->isCaseSensitive());
    }

    public function testArrayOnScalarBase()
    {
        $this->setExpectedException('Riimu\Kit\BaseConversion\DigitList\InvalidDigitException');
        (new NumberBase('ab'))->getValue([]);
    }
}
