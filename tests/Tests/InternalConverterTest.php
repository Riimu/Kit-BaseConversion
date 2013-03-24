<?php

use Riimu\Kit\NumberConversion\DecimalConverter\InternalConverter;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class InternalConverterTest extends PHPUnit_Framework_TestCase
{
    public function testAddition()
    {
        $method = $this->getMethod('add');
        $converter = new InternalConverter();

        $this->assertEquals('1000000000',
            $method->invokeArgs($converter, ['999999999', '1']));
        $this->assertEquals('1000000000000000000',
            $method->invokeArgs($converter, ['999999999999999999', '1']));
        $this->assertEquals(
            '2670151321593002918758104907580797343931592346904690',
            $method->invokeArgs($converter, [
                '2345624545734545234523452562346234887685957823452345',
                '324526775858457684234652345234562456245634523452345',
            ]));
    }

    public function testMultiplication()
    {
        $method = $this->getMethod('mul');
        $converter = new InternalConverter();

        $this->assertEquals('9872',
            $method->invokeArgs($converter, ['1234', '8']));
        $this->assertEquals('478826292',
            $method->invokeArgs($converter, ['54698', '8754']));
        $this->assertEquals('27481523481468',
            $method->invokeArgs($converter, ['654321987654', '42']));
        $this->assertEquals('428137263527481328423716',
            $method->invokeArgs($converter, ['654321987654', '654321987654']));

        $this->assertEquals(
            '761217971201691386666750803442316213515874236027244340864087844097227490577627886191459568955985999025',
            $method->invokeArgs($converter, [
                '2345624545734545234523452562346234887685957823452345',
                '324526775858457684234652345234562456245634523452345',
            ]));
    }

    public function testExponentiation()
    {
        $method = $this->getMethod('pow');
        $converter = new InternalConverter();

        $this->assertEquals('4',
            $method->invokeArgs($converter, ['2', '2']));
        $this->assertEquals('1099511627776',
            $method->invokeArgs($converter, ['2', '40']));
    }

    public function testDivision()
    {
        $method = $this->getMethod('div');
        $converter = new InternalConverter();

        $this->assertEquals(['2', '1'],
            $method->invokeArgs($converter, ['5', '2']));
        $this->assertEquals(['240698692', '2720'],
            $method->invokeArgs($converter, ['1099511627776', '4568']));
        $this->assertEquals(['240674625', '3041809741497875'],
            $method->invokeArgs($converter, [
                '1099511627776456789220000',
                '4568456789886541']));
        $this->assertEquals([
            '120411114009286733350003545175496252377468019183542312991243',
            '0'],
            $method->invokeArgs($converter, [
                '4334800104334322400600127626317865085588848690607523267684748',
                '36']));

    }

    private function getMethod($name)
    {
        $class = new ReflectionClass('Riimu\Kit\NumberConversion\DecimalConverter\InternalConverter');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
}
