<?php
declare(strict_types=1);
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

    public function testUnpackInvalidLength()
    {
        $packer = new DataPacker();
        // Create a header that says length is 100 but payload is only 3 bytes
        $header = pack('N', 100);
        $data   = $header . 'abc';

        $this->expectException(\UnexpectedValueException::class);
        $packer->unpack($data);
    }

    public function testPackToStreamWithoutStream()
    {
        $packer = new DataPacker();
        $this->expectException(\Error::class);
        $packer->packToStream("test");
    }

    public function testUnpackFromStreamWithoutStream()
    {
        $packer = new DataPacker();
        $this->expectException(\Error::class);
        $packer->unpackFromStream();
    }

    public function testDefaultSerializerWithoutIgbinary()
    {
        // Default constructor should work regardless of igbinary availability
        $packer = new DataPacker();
        $data   = $packer->pack(['key' => 'value']);
        $result = $packer->unpack($data);
        $this->assertEquals(['key' => 'value'], $result);
    }

    public function testPackAndUnpackScalar()
    {
        $packer = new DataPacker();
        $data   = $packer->pack(42);
        $result = $packer->unpack($data);
        $this->assertEquals(42, $result);
    }

    public function testPackToStreamWithClosedStream(): void
    {
        $packer = new DataPacker();
        $fh = fopen($this->tmpfile, 'w');
        $packer->attachStream($fh);
        fclose($fh);
        // Stream is now closed (not a resource)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Stream not ready when writing");
        $packer->packToStream("test");
    }

    public function testUnpackFromStreamWithClosedStream(): void
    {
        $packer = new DataPacker();
        $fh = fopen($this->tmpfile, 'w');
        $packer->attachStream($fh);
        fclose($fh);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Stream not ready when reading");
        $packer->unpackFromStream();
    }

    public function testUnpackFromStreamPartialData(): void
    {
        // Write only a header (4 bytes indicating length 100) but no payload
        $packer = new DataPacker();
        $header = pack('N', 100);
        file_put_contents($this->tmpfile, $header);

        $fh = fopen($this->tmpfile, 'r');
        $packer->attachStream($fh);
        // readFromStream will read header OK, then try to read 100 bytes of payload
        // but stream ends, so fread returns '' → returns null
        $result = $packer->unpackFromStream();
        fclose($fh);
        $this->assertNull($result);
    }

    public function testDefaultConstructorWithNullCallables(): void
    {
        // Passing null explicitly should use default serializer
        $packer = new DataPacker(null, null);
        $data   = $packer->pack(['a' => 1]);
        $result = $packer->unpack($data);
        $this->assertEquals(['a' => 1], $result);
    }
}
