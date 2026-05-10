<?php
declare(strict_types=1);

use Eris\Generators;
use Eris\TestTrait;
use Oasis\Mlib\Utils\DataPacker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DataPackerPropertyTest extends TestCase
{
    use TestTrait;

    // ─── Pack/unpack round-trip ─────────────────────────────────────────

    #[Test]
    public function packUnpackRoundTrip(): void
    {
        $serializableValue = Generators::oneOf(
            Generators::string(),
            Generators::int(),
            Generators::float(),
            Generators::bool(),
            Generators::constant(null),
        );

        $packer = new DataPacker();

        $this->forAll($serializableValue)
            ->then(function (mixed $value) use ($packer): void {
                $packed   = $packer->pack($value);
                $unpacked = $packer->unpack($packed);

                $this->assertEquals($value, $unpacked,
                    "DataPacker round-trip failed for type=" . gettype($value));
            });
    }

    // ─── Stream round-trip (multiple items) ─────────────────────────────

    #[Test]
    public function streamRoundTrip(): void
    {
        $this->forAll(
            Generators::vector(5, Generators::oneOf(
                Generators::string(),
                Generators::int(),
                Generators::bool(),
            )),
        )->then(function (array $items): void {
            $packer = new DataPacker();
            $tmpfile = tempnam(sys_get_temp_dir(), 'pbt-packer-');

            $fh = fopen($tmpfile, 'w');
            $packer->attachStream($fh);
            foreach ($items as $item) {
                $packer->packToStream($item);
            }
            fclose($fh);

            $fh = fopen($tmpfile, 'r');
            $packer->attachStream($fh);
            $results = [];
            while (($val = $packer->unpackFromStream()) !== null) {
                $results[] = $val;
            }
            fclose($fh);
            unlink($tmpfile);

            $this->assertCount(count($items), $results,
                "Stream round-trip must yield same number of items");
            foreach ($items as $i => $expected) {
                $this->assertEquals($expected, $results[$i],
                    "Stream round-trip item #$i mismatch");
            }
        });
    }
}
