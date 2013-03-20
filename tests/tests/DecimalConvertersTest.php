<?php

use Riimu\Kit\NumberConversion\DecimalConverter;

class DecimalConvertersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getConverterTestValues
     */
    public function testBCMathConverter ($number, $expected, $source, $target)
    {
        if (!function_exists('bcadd')) {
            $this->markTestSkipped('Missing BCMath functions');
        }

        $converter = new DecimalConverter\BCMathConverter();
        $this->assertEquals($expected, $converter->ConvertNumber($number, $source, $target));
    }

    /**
     * @dataProvider getConverterTestValues
     */
    public function testGMPConverter ($number, $expected, $source, $target)
    {
        if (!function_exists('gmp_add')) {
            $this->markTestSkipped('Missing GMP functions');
        }

        $converter = new DecimalConverter\GMPConverter();
        $this->assertEquals($expected, $converter->ConvertNumber($number, $source, $target));
    }

    public function getConverterTestValues ()
    {
        return [
            [[1, 1], [3], 2, 10],
            [[0], [0], 10, 10],
            [[10, 0, 9, 15, 15], [2, 4, 0, 4, 7, 7, 7], 16, 8],
        ];
    }
}
