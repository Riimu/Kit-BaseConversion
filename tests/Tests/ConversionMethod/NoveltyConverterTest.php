<?php

namespace Tests\ConversionMethod;

use Riimu\Kit\NumberConversion\ConversionMethod\NoveltyConverter;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class NoveltyConverterTest extends ConversionMethodTestBase
{
    protected $className = 'Riimu\Kit\NumberConversion\ConversionMethod\NoveltyConverter';

    public function testInvalidBase()
    {
        $this->assertFalse(NoveltyConverter::convert('0', '0', '01'));
    }

    public function testEmptyConvertParamater()
    {
        $this->assertSame('0', NoveltyConverter::convert('', '01', '0124567'));
    }

    public function testMissingNumberValue()
    {
        $this->assertFalse(NoveltyConverter::convert('2', '01', '0124567'));
    }
}
