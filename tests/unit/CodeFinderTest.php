<?php

require_once __DIR__ .'/../../src/Lib/CodeFinder.php';


use PHPUnit\Framework\TestCase;
use VfTest\Lib\CodeFinder;	


class CodeFinderTest extends TestCase
{
    public function testProcessInput() {
        $locationClient = $this->getMockBuilder('VfTest\Lib\LocationClient')->disableOriginalConstructor()->getMock();
        $finder = new CodeFinder($locationClient);
        $class = new ReflectionClass('VfTest\Lib\CodeFinder');
	$method = $class->getMethod('_processInput');
	$method->setAccessible(TRUE);
        $result = $method->invokeArgs($finder, [['1, ', ' 2 3, ', ' 4']]);
        $this->assertSame(['1','2 3','4'], $result);
    }
    
    
    /**
     * @expectedException Exception
     */
    public function testProcessInputFewCities() {
        $locationClient = $this->getMockBuilder('VfTest\Lib\LocationClient')->disableOriginalConstructor()->getMock();
        $finder = new CodeFinder($locationClient);
        $class = new ReflectionClass('VfTest\Lib\CodeFinder');
	$method = $class->getMethod('_processInput');
	$method->setAccessible(TRUE);
        $result = $method->invokeArgs($finder, [['1']]);
    }
    
    
    /**
     * @expectedException Exception
     */
    public function testProcessInputMuchCities() {
        $locationClient = $this->getMockBuilder('VfTest\Lib\LocationClient')->disableOriginalConstructor()->getMock();
        $finder = new CodeFinder($locationClient);
        $class = new ReflectionClass('VfTest\Lib\CodeFinder');
	$method = $class->getMethod('_processInput');
	$method->setAccessible(TRUE);
        $result = $method->invokeArgs($finder, [['1, ', '2, ', '3, ', '4']]);
    }
    
    
    public function testFormatSearchResultItem() {
        $locationClient = $this->getMockBuilder('VfTest\Lib\LocationClient')->disableOriginalConstructor()->getMock();
        $finder = new CodeFinder($locationClient);
        $class = new ReflectionClass('VfTest\Lib\CodeFinder');
	$method = $class->getMethod('_formatSearchResultItem');
	$method->setAccessible(TRUE);
        $result = $method->invokeArgs($finder, [['Town' => 'Town', 'County' => 'County', 'PostCode' => 'PostCode']]);
        $this->assertSame('Town, County: PostCode' . PHP_EOL, $result);
    }
    
    
    public function testFormatSearchResultSet() {
        $finder = $this->getMockBuilder('VfTest\Lib\CodeFinder')->setMethods(['_formatSearchResultItem'])->disableOriginalConstructor()->getMock();
        $finder->expects($this->exactly(2))->method('_formatSearchResultItem')->withConsecutive([['1']], [['2']])->willReturnOnConsecutiveCalls('2', '3');
        $class = new ReflectionClass('VfTest\Lib\CodeFinder');
	$method = $class->getMethod('_formatSearchResultSet');
	$method->setAccessible(TRUE);
        $result = $method->invokeArgs($finder, [[['1'],['2']]]);
        $this->assertSame('23', $result);
    }
    
    
    public function testFormatSearchResultSetEmpty() {
        $finder = $this->getMockBuilder('VfTest\Lib\CodeFinder')->setMethods(['_formatSearchResultItem'])->disableOriginalConstructor()->getMock();
        $class = new ReflectionClass('VfTest\Lib\CodeFinder');
	$method = $class->getMethod('_formatSearchResultSet');
	$method->setAccessible(TRUE);
        $result = $method->invokeArgs($finder, [[]]);
        $this->assertSame('No code found.' . PHP_EOL, $result);
    }
    
    
    public function testProcessOutput() {
        $finder = $this->getMockBuilder('VfTest\Lib\CodeFinder')->setMethods(['_formatSearchResultSet'])->disableOriginalConstructor()->getMock();
        $finder->expects($this->exactly(2))->method('_formatSearchResultSet')->withConsecutive([['2', '3']], [['5', '6']])->willReturnOnConsecutiveCalls('23', '56');
        $class = new ReflectionClass('VfTest\Lib\CodeFinder');
	$method = $class->getMethod('_processOutput');
	$method->setAccessible(TRUE);
        $result = $method->invokeArgs($finder, [['1' => ['2', '3'], '4' => ['5', '6']]]);
        $expected = 'Outward codes for your search "1":' . PHP_EOL . '23' . PHP_EOL . PHP_EOL . 'Outward codes for your search "4":' . PHP_EOL . '56' . PHP_EOL . PHP_EOL;
        $this->assertSame($expected, $result);
    }
    
    
    public function testFindPostalCodes() {
        $locationClient = $this->getMockBuilder('VfTest\Lib\LocationClient')->setMethods(['findPostalCode'])->disableOriginalConstructor()->getMock();
        $locationClient->expects($this->exactly(2))->method('findPostalCode')->withConsecutive(['1'], ['2'])->willReturnOnConsecutiveCalls('3', '4');
        $finder = $this->getMockBuilder('VfTest\Lib\CodeFinder')->setMethods(['_processInput', '_processOutput'])->disableOriginalConstructor()->getMock();
        $finder->setLocationClient($locationClient);
        $finder->expects($this->once())->method('_processInput')->with(['London', 'Birmingham'])->willReturn(['1', '2']);
        $finder->expects($this->once())->method('_processOutput')->with(['1' => '3', '2' => '4'])->willReturn('56');
        $result = $finder->findPostalCodes(['London', 'Birmingham']);
        $this->assertSame('56', $result);
    }
    
    
    
}