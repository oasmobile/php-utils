<?php
declare(strict_types=1);

use Oasis\Mlib\Utils\Exceptions\DataValidationException;
use PHPUnit\Framework\TestCase;

class DataValidationExceptionTest extends TestCase
{
    public function testCreateStaticMethod(): void
    {
        $exception = DataValidationException::create("test message", 42);
        $this->assertInstanceOf(DataValidationException::class, $exception);
        $this->assertEquals("test message", $exception->getMessage());
        $this->assertEquals(42, $exception->getCode());
    }

    public function testCreateWithPrevious(): void
    {
        $previous  = new \RuntimeException("previous");
        $exception = DataValidationException::create("msg", 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testWithFieldName(): void
    {
        $exception = DataValidationException::create("msg");
        $result    = $exception->withFieldName("myField");
        $this->assertSame($exception, $result);
        $this->assertEquals("myField", $exception->getFieldName());
    }

    public function testSetFieldName(): void
    {
        $exception = new DataValidationException("msg");
        $exception->setFieldName("field1");
        $this->assertEquals("field1", $exception->getFieldName());
    }

    /**
     * @see ISS-3.1.0-L01
     * getFieldName() should return empty string when fieldName is not set,
     * instead of throwing "must not be accessed before initialization".
     */
    public function testGetFieldNameWithoutSettingReturnsEmptyString(): void
    {
        $exception = new DataValidationException("some message");
        $this->assertSame('', $exception->getFieldName());
    }

    /**
     * @see ISS-3.1.0-L01
     * Subclass should also be safe to call getFieldName() without initialization.
     */
    public function testSubclassGetFieldNameWithoutSettingReturnsEmptyString(): void
    {
        $exception = new \Oasis\Mlib\Utils\Exceptions\MandatoryValueMissingException("missing");
        $this->assertSame('', $exception->getFieldName());
    }
}
