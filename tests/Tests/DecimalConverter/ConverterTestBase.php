<?php

namespace Tests\DecimalConverter;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class ConverterTestBase extends \PHPUnit_Framework_TestCase
{
    private static $methods;

    private $converter;

    abstract public function createConverter();

    public static function setUpBeforeClass()
    {
        self::$methods = [];
    }

    /* NUMBER AND FRACTION CONVERSION TESTS */

    public function testSettingDefaultPrecision()
    {
        $conv = $this->getConverter();
        $conv->setDefaultPrecision(1);
        $this->assertSame([1], $conv->convertFractions([3], 4, 2));
    }

    /**
     * @dataProvider getNumberConversionData
     */
    public function testNumberConversion($input, $result, $source, $target)
    {
        $conv = $this->getConverter();
        $this->assertSame($result, $conv->convertNumber($input, $source, $target));
        $this->assertSame($input, $conv->convertNumber($result, $target, $source));
    }

    public function getNumberConversionData ()
    {
        return [
            [[1, 1], [3], 2, 10],
            [[0], [0], 10, 10],
            [[10, 0, 9, 15, 15], [2, 4, 0, 4, 7, 7, 7], 16, 8],
        ];
    }

    /**
     * @dataProvider getFractionConversionData
     */
    public function testFractionConversion($input, $result, $source, $target, $precision)
    {
        $conv = $this->getConverter();
        $this->assertSame($result, $conv->convertFractions($input, $source, $target, $precision));
    }

    public function getFractionConversionData()
    {
        return [
            [[1], [5], 2, 10, 0],
            [[2], [6, 6, 7], 3, 10, 3],
            [[1], [3, 3], 3, 10, -1],
            [[7, 5], [1], 10, 2, 1],
            [[1, 4], [1, 0, 7, 5, 3, 4, 1, 2, 1,7], 10, 8, 10],
            [[1, 4], [0, 0, 1, 0, 0, 1, 0, 0, 0], 10, 2, 9],
            [[1, 4], [0, 0, 1, 0], 10, 2, 4],
            [[4, 2], [0, 1, 1, 0, 1, 1, 0, 0], 10, 2, -1],
        ];
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
        $conv = $this->getConverter();

        if (!isset(self::$methods[$method])) {
            $call = new \ReflectionMethod($conv, $method);
            $call->setAccessible(true);
            self::$methods[$method] = $call;
        }

        return self::$methods[$method]->invokeArgs($conv, $args);
    }

    protected function getConverter()
    {
        if (!isset($this->converter)) {
            $this->converter = $this->createConverter();
        }

        return $this->converter;
    }
}
