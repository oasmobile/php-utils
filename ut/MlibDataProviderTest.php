<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-09-15
 * Time: 19:04
 */

use Oasis\Mlib\Utils\ArrayDataProvider;
use Oasis\Mlib\Utils\Exceptions\DataEmptyException;
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;
use Oasis\Mlib\Utils\Exceptions\MandatoryValueMissingException;

class MlibDataProviderTest extends PHPUnit_Framework_TestCase
{
    /** @var ArrayDataProvider */
    protected $dp = null;
    
    protected function setUp()
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
        $this->assertTrue($this->dp->has('int', ArrayDataProvider::INT_TYPE));
        $this->assertTrue($this->dp->has('float', ArrayDataProvider::FLOAT_TYPE));
        $this->assertTrue($this->dp->has('string', ArrayDataProvider::STRING_TYPE));
        $this->assertTrue($this->dp->has('empty', ArrayDataProvider::STRING_TYPE));
        $this->assertTrue($this->dp->has('array'));
        $this->assertTrue($this->dp->has('array', ArrayDataProvider::ARRAY_TYPE));
        $this->assertTrue($this->dp->has('object'));
        $this->assertTrue($this->dp->has('object', ArrayDataProvider::OBJECT_TYPE));
    }
    
    public function testGet()
    {
        $this->assertEquals(1, $this->dp->getMandatory("int", ArrayDataProvider::INT_TYPE));
        $this->assertEquals(1, $this->dp->getMandatory("int", ArrayDataProvider::FLOAT_TYPE));
        $this->assertEquals(2.4, $this->dp->getMandatory("float", ArrayDataProvider::FLOAT_TYPE));
        $this->assertEquals('name', $this->dp->getMandatory("string", ArrayDataProvider::STRING_TYPE));
        $this->assertEquals(true, $this->dp->getMandatory("bool", ArrayDataProvider::BOOL_TYPE));
        $this->assertEquals(true, $this->dp->getMandatory("bool_str_on", ArrayDataProvider::BOOL_TYPE));
        
        $this->assertInstanceOf(
            \stdClass::class,
            $this->dp->getMandatory("object", ArrayDataProvider::OBJECT_TYPE)
        );
        $this->assertNotEquals(0, $this->dp->getMandatory("string", ArrayDataProvider::MIXED_TYPE));
        $this->assertEquals('name', $this->dp->getMandatory("string", ArrayDataProvider::MIXED_TYPE));
    }
    
    /**
     * @dataProvider
     */
    public function testNull()
    {
        $this->expectException(MandatoryValueMissingException::class);
        $this->dp->getMandatory('null', ArrayDataProvider::INT_TYPE);
    }
    
    public function getValidatorsForNullTest()
    {
        return [
            [ArrayDataProvider::INT_TYPE],
            [ArrayDataProvider::FLOAT_TYPE],
            [ArrayDataProvider::STRING_TYPE],
            [ArrayDataProvider::BOOL_TYPE],
            [ArrayDataProvider::ARRAY_TYPE],
            [ArrayDataProvider::MIXED_TYPE],
        ];
    }
    
    public function testNonEmpytString()
    {
        $this->assertEquals('', $this->dp->getMandatory('empty', ArrayDataProvider::STRING_TYPE));
        $this->expectException(DataEmptyException::class);
        $this->dp->getMandatory('empty', ArrayDataProvider::NON_EMPTY_STRING_TYPE);
    }
    
    public function testHierarchicalGet()
    {
        $this->assertEquals(55, $this->dp->getMandatory("a.b.c", ArrayDataProvider::INT_TYPE));
        $this->assertEquals(33, $this->dp->getMandatory("a.b.d.g", ArrayDataProvider::INT_TYPE));
        $this->assertEquals(66, $this->dp->getMandatory("a.d.e", ArrayDataProvider::INT_TYPE));
        $this->assertEquals('y', $this->dp->getMandatory("a.x", ArrayDataProvider::STRING_TYPE));
        
        $this->expectException(MandatoryValueMissingException::class);
        $this->dp->getMandatory('a.b.c.d');
    }
    
    public function testPathPushPop()
    {
        $this->dp->pushPath('a');
        $this->assertTrue(is_array($this->dp->getMandatory('b', ArrayDataProvider::ARRAY_TYPE)));
        $this->assertEquals(55, $this->dp->getMandatory('b.c', ArrayDataProvider::INT_TYPE));
        $this->dp->pushPath('b');
        $this->assertEquals(55, $this->dp->getMandatory('c', ArrayDataProvider::INT_TYPE));
        $this->assertEquals(33, $this->dp->getMandatory('d.g', ArrayDataProvider::INT_TYPE));
        
        $this->dp->popPath();
        $this->assertEquals(66, $this->dp->getMandatory("d.e", ArrayDataProvider::INT_TYPE));
        $this->dp->pushPath('d');
        $this->assertEquals(77, $this->dp->getMandatory("e", ArrayDataProvider::INT_TYPE));
        
        $this->dp->setCurrentPath('');
        $this->assertEquals(66, $this->dp->getMandatory('a.d.e', ArrayDataProvider::INT_TYPE));
    }
    
    public function test2DArrayGet()
    {
        $a = $this->dp->getMandatory('2darray', ArrayDataProvider::ARRAY_2D_TYPE);
        $this->assertTrue(is_array($a));
        foreach ($a as $idx => $val) {
            $this->assertTrue(is_array($val), "for 'a', value at #$idx is not array, value = " . json_encode($val));
        }
    }
    
    public function testInvalidDataTypeExpectingArray()
    {
        $this->dp->getMandatory('int', ArrayDataProvider::INT_TYPE);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->dp->getMandatory('int', ArrayDataProvider::ARRAY_TYPE);
    }
    
    public function testInvalidDataTypeExpectingNotArray()
    {
        $this->dp->getMandatory('array', ArrayDataProvider::ARRAY_TYPE);
        
        $this->expectException(InvalidDataTypeException::class);
        $this->dp->getMandatory('array', ArrayDataProvider::INT_TYPE);
    }
    
    public function testMandatoryOk()
    {
        $this->dp->getMandatory("int");
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
        $val = $this->dp->getOptional("java", ArrayDataProvider::STRING_TYPE, "bean");
        $this->assertEquals($val, "bean");
    }
    
    public function testOptionalWithoutDefault()
    {
        $val = $this->dp->getOptional("java", ArrayDataProvider::STRING_TYPE);
        $this->assertEquals($val, null);
        $this->assertTrue($val !== '');
    }
    
    public function testOptionalExist()
    {
        $this->assertEquals(true, $this->dp->getOptional("bool", ArrayDataProvider::BOOL_TYPE, false));
    }
}
