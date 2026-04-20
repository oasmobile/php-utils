<?php
use Oasis\Mlib\Utils\DataPacker;
use Oasis\Mlib\Utils\StringUtils;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-09-16
 * Time: 17:21
 */
class DataPackerTest extends TestCase
{
    protected $tmpfile;

    protected function setUp(): void
    {
        $this->tmpfile = tempnam(sys_get_temp_dir(), "data-packer-test");
    }

    protected function tearDown(): void
    {
        unlink($this->tmpfile);
    }

    public function testPackingAndUnpacking()
    {
        $obj = new StringUtils();

        $packer = new DataPacker();
        $data   = $packer->pack($obj);
        $this->assertTrue(is_string($data));
        $this->assertGreaterThan(4, strlen($data));

        $unpacked = $packer->unpack($data);
        $this->assertInstanceOf(StringUtils::class, $unpacked);
    }

    public function testStreamOperation()
    {
        $obj = new StringUtils();

        $packer = new DataPacker();
        $fh     = fopen($this->tmpfile, 'w');
        $packer->attachStream($fh);

        $packer->packToStream($obj);
        $packer->packToStream($obj);
        $packer->packToStream($obj);
        fclose($fh);

        $fh = fopen($this->tmpfile, 'r');
        $packer->attachStream($fh);
        $count = 0;
        while ($obj = $packer->unpackFromStream()) {
            $this->assertInstanceOf(StringUtils::class, $obj);
            $count++;
        }
        $this->assertEquals($count, 3);
    }

    public function testUsingSystemSerializer()
    {
        $obj = new StringUtils();

        $packer = new DataPacker("serialize", "unserialize");
        $data   = $packer->pack($obj);
        $this->assertTrue(is_string($data));
        $this->assertGreaterThan(4, strlen($data));

        $unpacked = $packer->unpack($data);
        $this->assertInstanceOf(StringUtils::class, $unpacked);
    }
}
