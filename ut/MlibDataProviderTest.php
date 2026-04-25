<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-09-15
 * Time: 19:04
 */

use Oasis\Mlib\Utils\ArrayDataProvider;
use Oasis\Mlib\Utils\DataType;
use Oasis\Mlib\Utils\Exceptions\DataEmptyException;
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Exceptions\MandatoryValueMissingException;
use PHPUnit\Framework\TestCase;

class MlibDataProviderTest extends TestCase
{
    /** @var ArrayDataProvider */
    protected $dp = null;
    
    protected function setUp(): void
    {
        $data     = [
            "int"          => 1,
            "float"        => 2.4,
            "string"       => "name",
            "empty"        => "",
            "array"        => [
                0,
                1,
                2,
            ],
            "null"         => null,
            "object"       => new \stdClass(),
            "bool"         => true,
            "bool_str_on"  => "on",
            "bool_str_off" => "off",
            "a"            => [
                "b"   => [
                    "c" => 55,
                    "d" => [
                        "g" => 33,
                    ],
                ],
                "d.e" => 66,
                "d"   => [
                    "e" => 77,
                ],
            ],
            "2darray"      => [
                [1, 2],
                [3, 4],
                [5, 6],
            ],
            "a.x"          => "y",
        
        ];
        $this->dp = new ArrayDataProvider($data);
    }
    
    public function testHas()
    {
        $this->assertTrue($this->dp->has('int'));
        $this->assertTrue($this->dp->has('int', DataType::Int));
        $this->assertTrue($this->dp->has('float', DataType::Float));
        $this->assertTrue($this->dp->has('string', DataType::String));
        $this->assertTrue($this->dp->has('empty', DataType::String));
        $this->assertTrue($this->dp->has('array'));
        $this->assertTrue($this->dp->has('array', DataType::Array));
        $this->assertTrue($this->dp->has('object'));
        $this->assertTrue($this->dp->has('object', DataType::Object));
    }
    
    public function testGet()
    {
        $this->assertEquals(1, $this->dp->getMandatory("int", DataType::Int));
        $this->assertEquals(1, $this->dp->getMandatory("int", DataType::Float));
        $this->assertEquals(2.4, $this->dp->getMandatory("float", DataType::Float));
        $this->assertEquals('name', $this->dp->getMandatory("string", DataType::String));
        $this->assertEquals(true, $this->dp->getMandatory("bool", DataType::Bool));
        $this->assertEquals(true, $this->dp->getMandatory("bool_str_on", DataType::Bool));
        
        $this->assertInstanceOf(
            \stdClass::class,
            $this->dp->getMandatory("object", DataType::Object)
        );
        $this->assertNotEquals(0, $this->dp->getMandatory("string", DataType::Mixed));
        $this->assertEquals('name', $this->dp->getMandatory("string", DataType::Mixed));
    }
    
    public function testNull()
    {
        $this->expectException(MandatoryValueMissingException::class);
        $this->dp->getMandatory('null', DataType::Int);
    }
    
    public function testNonEmpytString()
    {
        $this->assertEquals('', $this->dp->getMandatory('empty', DataType::String));
        $this->expectException(DataEmptyException::class);
        $this->dp->getMandatory('empty', DataType::NonEmptyString);
    }
    
    public function testHierarchicalGet()
    {
        $this->assertEquals(55, $this->dp->getMandatory("a.b.c", DataType::Int));
        $this->assertEquals(33, $this->dp->getMandatory("a.b.d.g", DataType::Int));
        $this->assertEquals(66, $this->dp->getMandatory("a.d.e", DataType::Int));
        $this->assertEquals('y', $this->dp->getMandatory("a.x", DataType::String));
        
        $this->expectException(MandatoryValueMissingException::class);
        $this->dp->getMandatory('a.b.c.d');
    }
    
    public function testPathPushPop()
    {
        $this->dp->pushPath('a');
        $this->assertTrue(is_array($this->dp->getMandatory('b', DataType::Array)));
        $this->assertEquals(55, $this->dp->getMandatory('b.c', DataType::Int));
        $this->dp->pushPath('b');
        $this->assertEquals(55, $this->dp->getMandatory('c', DataType::Int));
        $this->assertEquals(33, $this->dp->getMandatory('d.g', DataType::Int));
        
        $this->dp->popPath();
        $this->assertEquals(66, $this->dp->getMandatory("d.e", DataType::Int));
        $this->dp->pushPath('d');
        $this->assertEquals(77, $this->dp->getMandatory("e", DataType::Int));
        
        $this->dp->setCurrentPath('');
        $this->assertEquals(66, $this->dp->getMandatory('a.d.e', DataType::Int));
    }
    
    public function test2DArrayGet()
    {
        $a = $this->dp->getMandatory('2darray', DataType::Array2D);
        $this->assertTrue(is_array($a));
        foreach ($a as $idx => $val) {
            $this->assertTrue(is_array($val), "for 'a', value at #$idx is not array, value = " . json_encode($val));
        }
    }
    
    public function testInvalidDataTypeExpectingArray()
    {
        $this->dp->getMandatory('int', DataType::Int);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->dp->getMandatory('int', DataType::Array);
    }
    
    public function testInvalidDataTypeExpectingNotArray()
    {
        $this->dp->getMandatory('array', DataType::Array);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->dp->getMandatory('array', DataType::Int);
    }
    
    public function testMandatoryOk()
    {
        $this->dp->getMandatory("int");
        $this->assertTrue(true);
    }
    
    public function testMandatoryNotExist()
    {
        $this->expectException(MandatoryValueMissingException::class);
        $this->dp->getMandatory("java");
    }
    
    public function testMandatoryValueMissingWithKey()
    {
        try {
            $this->dp->getMandatory("java");
        } catch (MandatoryValueMissingException $e) {
            $this->assertEquals('java', $e->getFieldName());
        }
    }
    
    public function testOptionalNotExist()
    {
        $val = $this->dp->getOptional("java", DataType::String, "bean");
        $this->assertEquals($val, "bean");
    }
    
    public function testOptionalWithoutDefault()
    {
        $val = $this->dp->getOptional("java", DataType::String);
        $this->assertEquals($val, null);
        $this->assertTrue($val !== '');
    }
    
    public function testOptionalExist()
    {
        $this->assertEquals(true, $this->dp->getOptional("bool", DataType::Bool, false));
    }
}
