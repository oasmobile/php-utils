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
    protected $stream;
    protected $buffer       = '';
    protected $serializer;
    protected $unserializer;

    function __construct($serializer = null, $unserializer = null)
    {
        if (is_callable($serializer)) {
            $this->serializer = $serializer;
        }
        if (is_callable($unserializer)) {
            $this->unserializer = $unserializer;
        }

        if ($this->serializer === null) {
            $this->serializer = function_exists('igbinary_serialize')
                ? 'igbinary_serialize'
                : 'serialize';
        }
        if ($this->unserializer === null) {
            $this->unserializer = function_exists('igbinary_unserialize')
                ? 'igbinary_unserialize'
                : 'unserialize';
        }
    }

    public function pack($dataObject)
    {
        $serialized = call_user_func($this->serializer, $dataObject);
        $len        = strlen($serialized);
        $header     = pack('N', $len);

        return $header . $serialized;
    }

    public function packToStream($dataObject)
    {
        if (!is_resource($this->stream)) {
            throw new \RuntimeException("Stream not ready when writing to it");
        }

        $data = $this->pack($dataObject);
        fwrite($this->stream, $data);
    }

    public function unpack($data)
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

    public function unpackFromStream()
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

    public function attachStream($stream)
    {
        $this->stream = $stream;
        $this->buffer = '';
    }

    protected function readFromStream($maxSize)
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
