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
        new NumberBase(true);
    }

    public function testCreateDefaultIntegerBase ()
    {
        $base = new NumberBase(18);
        $this->assertEquals(18, $base->getRadix());
        $this->assertEquals('G', $base->getFromDecimalValue(16));
        $this->assertEquals(17, $base->getDecimalValue('H'));
    }
    
    public function testCreateBase64IntegerBase ()
    {
        $base = new NumberBase(64);
        $this->assertEquals('A', $base->getFromDecimalValue(0));
        $this->assertEquals(62, $base->getDecimalValue('+'));
    }
    
    public function testCreateByteIntegerBase ()
    {
        $base = new NumberBase(256);
        $this->assertEquals("\x64", $base->getFromDecimalValue(0x64));
        $this->assertEquals(032, $base->getDecimalValue("\032"));
    }
    
    public function testCreateLargeIntegerBase ()
    {
        $base = new NumberBase(512);
        $this->assertEquals("#306;", $base->getFromDecimalValue(306));
        $this->assertEquals(32, $base->getDecimalValue("#32;"));
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
    public function testBaseWithSingleNumber ()
    {
        new NumberBase(array(0));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBaseWithDuplicateNumbers ()
    {
        new NumberBase(array(0, 0, 1));
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testBaseWithMissingValues ()
    {
        new NumberBase(array(0 => 0, 2 => 1));
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
        
        $table = array(
            array_map('str_split', array_keys($result)),
            array_map('str_split', array_values($result)),
        );

        $this->assertEquals($table, $a->createConversionTable($b));
        
        $temp = $table[0];
        $table[0] = $table[1];
        $table[1] = $temp;
        
        $this->assertEquals($table, $b->createConversionTable($a));
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
    
    public function testObjectConversionTable ()
    {
        $std1 = new \stdClass();
        $std1->value = 0;
        $std2 = new \stdClass();
        $std2->value = 1;
        
        $a = new NumberBase(array($std1, $std2));
        $b = new NumberBase(array($std2, $std1));
        
        $this->assertEquals(array(
            array(array($std1), array($std2)),
            array(array($std2), array($std1)),
        ), $a->createConversionTable($b));
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
