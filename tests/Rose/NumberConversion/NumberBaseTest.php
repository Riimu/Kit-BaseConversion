<?php

namespace Rose\NumberConversion;

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
        $base = new NumberBase(true);
    }

    public function testCreateWithInteger ()
    {
        $base = new NumberBase(18);
        $this->assertEquals(18, $base->getRadix());
        $this->assertEquals('G', $base->getFromDecimalValue(16));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateWithTooLargeInteger ()
    {
        $vase = new NumberBase(99);
    }

    public function testCreateWithString ()
    {
        $base = new NumberBase('ABCDEF');
        $this->assertEquals(6, $base->getRadix());
        $this->assertEquals(4, $base->getDecimalValue('E'));
    }

    public function testCreateWithArray ()
    {
        $base = new NumberBase(array('foo', 'bar'));
        $this->assertEquals(2, $base->getRadix());
        $this->assertEquals(0, $base->getDecimalValue('foo'));
        $this->assertEquals('bar', $base->getFromDecimalValue(1));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testTooSmallBase ()
    {
        $base = new NumberBase('0');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidNumberBase ()
    {
        $base = new NumberBase(array('a', 'a', 'b'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGettingMissingDecimalValue ()
    {
        $base = new NumberBase(16);
        $base->getFromDecimalValue(17);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGettingMissingCharacter ()
    {
        $base = new NumberBase(16);
        $base->getDecimalValue('G');
    }

    /**
     * @dataProvider getExponentialBaseTestValues
     */
    public function testExponentialBases ($a, $b, $equals)
    {
        $aBase = new NumberBase($a);
        $bBase = new NumberBase($b);
        $this->assertEquals($equals, $aBase->isExponentialBase($bBase));
        $this->assertEquals($equals, $bBase->isExponentialBase($aBase));
    }

    public function getExponentialBaseTestValues ()
    {
        return array(
            array(2, 16, true), array(5, 25, true),
            array(5, 20, false), array(3, 17, false)
        );
    }

    /**
     * @dataProvider getConversionTableTestValues
     */
    public function testConversionTable ($a, $b, $result)
    {
        $a = new NumberBase($a);
        $b = new NumberBase($b);

        $this->assertEquals($result, $a->createConversionTable($b));
        $this->assertEquals(array_flip($result), $b->createConversionTable($a));
    }
    
    public function getConversionTableTestValues ()
    {
        return array(
            array(2, 16, array(
                '0000' => '0', '0001' => '1', '0010' => '2', '0011' => '3',
                '0100' => '4', '0101' => '5', '0110' => '6', '0111' => '7',
                '1000' => '8', '1001' => '9', '1010' => 'A', '1011' => 'B',
                '1100' => 'C', '1101' => 'D', '1110' => 'E', '1111' => 'F',
            )),
            array('!#%', 'ABCDEFGHI', array(
                '!!' => 'A', '!#' => 'B', '!%' => 'C', '#!' => 'D', '##' => 'E',
                '#%' => 'F', '%!' => 'G', '%#' => 'H', '%%' => 'I',
            )),
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConversionTableException ()
    {
        $a = new NumberBase(5);
        $b = new NumberBase(20);
        $a->createConversionTable($b);
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
        return array(
            array(4, 8, 2),
            array(4, 16, 4),
        );
    }
}
