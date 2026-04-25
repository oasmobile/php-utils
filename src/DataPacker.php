<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-09-16
 * Time: 16:54
 */

namespace Oasis\Mlib\Utils;

class DataPacker
{
    protected mixed $stream;
    protected string $buffer       = '';
    /** @var callable */
    protected $serializer;
    /** @var callable */
    protected $unserializer;

    public function __construct(?callable $serializer = null, ?callable $unserializer = null)
    {
        if (is_callable($serializer)) {
            $this->serializer = $serializer;
        }
        if (is_callable($unserializer)) {
            $this->unserializer = $unserializer;
        }

        if (!isset($this->serializer)) {
            $this->serializer = function_exists('igbinary_serialize')
                ? 'igbinary_serialize'
                : 'serialize';
        }
        if (!isset($this->unserializer)) {
            $this->unserializer = function_exists('igbinary_unserialize')
                ? 'igbinary_unserialize'
                : 'unserialize';
        }
    }

    public function pack(mixed $dataObject): string
    {
        $serialized = call_user_func($this->serializer, $dataObject);
        $len        = strlen($serialized);
        $header     = pack('N', $len);

        return $header . $serialized;
    }

    public function packToStream(mixed $dataObject): void
    {
        if (!is_resource($this->stream)) {
            throw new \RuntimeException("Stream not ready when writing to it");
        }

        $data = $this->pack($dataObject);
        fwrite($this->stream, $data);
    }

    public function unpack(string $data): mixed
    {
        $header   = substr($data, 0, 4);
        $unpacked = unpack('Nlen', $header);
        $len      = $unpacked['len'];
        $payroll  = substr($data, 4);
        if ($len != strlen($payroll)) {
            throw new \UnexpectedValueException(
                "Data to be unpacked has different length than what is specified in header."
            );
        }

        $unserialized = call_user_func($this->unserializer, $payroll);

        return $unserialized;
    }

    public function unpackFromStream(): mixed
    {
        $header = $this->readFromStream(4);
        if ($header == '') {
            return null;
        }

        $unpacked = unpack('Nlen', $header);
        $len      = $unpacked['len'];
        $payroll  = $this->readFromStream($len);
        if ($payroll == '') {
            return null;
        }

        $unserialized = call_user_func($this->unserializer, $payroll);

        return $unserialized;
    }

    public function attachStream(mixed $stream): void
    {
        $this->stream = $stream;
        $this->buffer = '';
    }

    protected function readFromStream(int $maxSize): string
    {
        if (!is_resource($this->stream)) {
            throw new \RuntimeException("Stream not ready when reading from it");
        }

        while (strlen($this->buffer) < $maxSize) {
            $local_buf = fread($this->stream, $maxSize);
            if ($local_buf === false) {
                throw new \UnexpectedValueException("Cannot read data from stream");
            }
            elseif ($local_buf === '') {
                return '';
            }
            $this->buffer .= $local_buf;
        }

        $ret          = substr($this->buffer, 0, $maxSize);
        $this->buffer = substr($this->buffer, $maxSize);

        return $ret;

    }
}
