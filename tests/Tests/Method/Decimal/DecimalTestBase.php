<?php

namespace Tests\DecimalConverter;

use Tests\ConversionMethod\ConversionMethodTestBase;
use Riimu\Kit\NumberConversion\NumberBase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class ConverterTestBase extends ConversionMethodTestBase
{
    private static $methods;
    private $converter;

    public static function setUpBeforeClass()
    {
        self::$methods = [];
    }

    /* FRACTION CONVERSION TESTS */

    /**
     * @dataProvider getFractionConversionData
     */
    public function testFractionConversion($input, $result, $source, $target, $precision)
    {
        $conv = $this->getConverter($source, $target);
        $conv->setPrecision($precision);
        $this->assertSame(str_split($result), $conv->convertFractions(str_split($input)));
    }

    public function getFractionConversionData()
    {
        return [
            ['1', '5', 2, 10, 0],
            ['2', '667', 3, 10, 3],
            ['1', '33', 3, 10, -1],
            ['75', '1', 10, 2, 1],
            ['14', '1075341217', 10, 8, 10],
            ['14', '001001000', 10, 2, 9],
            ['14', '0010001111', 10, 2, 10],
            ['14', '0010', 10, 2, 4],
            ['42', '01101100', 10, 2, -1],
        ];
    }

    /* TEST LACK OF SUPPORT */

    /**
     * @expectedException Riimu\Kit\NumberConversion\ConversionMethod\ConversionException
     */
    public function testMissingNumberConversionSupport()
    {
        $obj = $this->getMock($this->className, ['isSupported'],
            [new NumberBase(4), new NumberBase(16)]);
        $obj->expects($this->once())->method('isSupported')->will($this->returnValue(false));
        $obj->convertNumber(['1']);
    }

    /**
     * @expectedException Riimu\Kit\NumberConversion\ConversionMethod\ConversionException
     */
    public function testMissingFractionConversionSupport()
    {
        $obj = $this->getMock($this->className, ['isSupported'],
            [new NumberBase(4), new NumberBase(16)]);
        $obj->expects($this->once())->method('isSupported')->will($this->returnValue(false));
        $obj->convertFractions(['1']);
    }

    /* PROTECTED MATHEMATICAL FUNCTION TESTS */

    public function testInitialization()
    {
        $this->assertSame('42', $this->val($this->init('42')));
    }

    /**
     * @dataProvider getAdditionData
     */
    public function testAddition($a, $b, $result)
    {
        $this->assertSame($result, $this->val($this->invoke('add', [
            $this->init($a), $this->init($b)
        ])));
    }

    public function getAdditionData()
    {
        return [
            ['42', '42', '84'],
            ['1099511627776', '0', '1099511627776'],
            ['0', '1099511627776', '1099511627776'],
            ['34359738368', '17179869184', '51539607552'],
            ['658745633215465787963', '6565899', '658745633215472353862'],
            ['1', '999999999999999999', '1000000000000000000'],
            [
                '2345624545734545234523452562346234887685957823452345',
                '324526775858457684234652345234562456245634523452345',
                '2670151321593002918758104907580797343931592346904690',
            ],
        ];
    }

    /**
     * @dataProvider getMultiplicationData
     */
    public function testMultiplication($a, $b, $result)
    {
        $this->assertSame($result, $this->val($this->invoke('mul', [
            $this->init($a), $this->init($b)
        ])));
    }

    public function getMultiplicationData()
    {
        return [
            ['42', '42', '1764'],
            ['1', '1099511627776', '1099511627776'],
            ['1099511627776', '1', '1099511627776'],
            ['1099511627776', '0', '0'],
            ['654321987654', '42', '27481523481468'],
            ['8589934592', '8580034592', '73701935942377406464'],
            ['654321987654', '654321987654', '428137263527481328423716'],
            [
                '2345624545734545234523452562346234887685957823452345',
                '324526775858457684234652345234562456245634523452345',
                '761217971201691386666750803442316213515874236027244340864087844097227490577627886191459568955985999025'
            ],
        ];
    }

    /**
     * @dataProvider getExponentiationData
     */
    public function testExponentiation($a, $b, $result)
    {
        $this->assertSame($result, $this->val($this->invoke('pow', [
            $this->init($a), (int) $b
        ])));
    }

    public function getExponentiationData()
    {
        return [
            ['4', '13', '67108864'],
            ['1', '42', '1'],
            ['4294967296', '1', '4294967296'],
            ['4294967296', '0', '1'],
            ['2', '40', '1099511627776'],
            ['42', '42', '150130937545296572356771972164254457814047970568738777235893533016064'],
        ];
    }

    /**
     * @dataProvider getDivisionData
     */
    public function testDivision($a, $b, $result)
    {
        list($div, $mod) = $this->invoke('div', [$this->init($a), $this->init($b)]);
        $this->assertSame($result, [$this->val($div), $this->val($mod)]);
    }

    public function getDivisionData()
    {
        return [
            ['42', '42', ['1', '0']],
            ['2147483648', '4294967296', ['0', '2147483648']],
            ['1099511627776', '4568', ['240698692', '2720']],
            ['1099511627776456789220000', '4568456789886541', ['240674625', '3041809741497875']],
            ['1099511627776456789220000', '369258741', ['2977618416828369', '39196571']],
            [
                '4334800104334322400600127626317865085588848690607523267684748', '36',
                ['120411114009286733350003545175496252377468019183542312991243', '0']
            ],
        ];
    }

    private function init ($number)
    {
        return $this->invoke('init', [$number]);
    }

    private function val ($number)
    {
        return $this->invoke('val', [$number]);
    }

    private function invoke($method, array $args)
    {
        if (!isset($this->converter)) {
            $this->converter = $this->getConverter(16, 32);
        }

        $conv = $this->converter;

        if (!isset(self::$methods[$method])) {
            $call = new \ReflectionMethod($conv, $method);
            $call->setAccessible(true);
            self::$methods[$method] = $call;
        }

        return self::$methods[$method]->invokeArgs($conv, $args);
    }
}
