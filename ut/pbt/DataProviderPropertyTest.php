<?php
declare(strict_types=1);

use Eris\Generators;
use Eris\TestTrait;
use Oasis\Mlib\Utils\ArrayDataProvider;
use Oasis\Mlib\Utils\DataType;
use Oasis\Mlib\Utils\Exceptions\DataValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DataProviderPropertyTest extends TestCase
{
    use TestTrait;

    // ─── Behavior preservation (determinism) ────────────────────────────

    #[Test]
    public function behaviorPreservation(): void
    {
        $this->forAll(
            Generators::int(),
            Generators::float(),
            Generators::string(),
            Generators::bool(),
        )->then(function (int $intVal, float $floatVal, string $strVal, bool $boolVal): void {
            $data = [
                'intVal'   => $intVal,
                'floatVal' => $floatVal,
                'strVal'   => $strVal,
                'boolVal'  => $boolVal,
                'arrVal'   => [$intVal, $intVal + 1],
                'objVal'   => (object)['a' => 1],
                'nested'   => ['child' => ['deep' => $intVal]],
            ];

            $dp = new ArrayDataProvider($data);

            foreach (DataType::cases() as $dataType) {
                foreach (['intVal', 'floatVal', 'strVal', 'boolVal'] as $key) {
                    $h1 = $dp->has($key, $dataType);
                    $h2 = $dp->has($key, $dataType);
                    $this->assertSame($h1, $h2, "has() determinism for key=$key, type={$dataType->name}");
                }
                $this->assertFalse($dp->has('nonexistent_key', $dataType));
            }

            $this->assertSame($intVal, $dp->getOptional('nested.child.deep', DataType::Int));
        });
    }

    // ─── Exception carries fieldName ────────────────────────────────────

    #[Test]
    public function setsFieldNameOnException(): void
    {
        $this->forAll(
            Generators::string(),
            Generators::int(),
        )->then(function (string $key, int $intVal): void {
            if ($key === '') return;

            $dp = new ArrayDataProvider([$key => $intVal]);

            try {
                $dp->getMandatory($key, DataType::Array);
            } catch (DataValidationException $e) {
                $this->assertSame($key, $e->getFieldName());
            }
        });
    }

    // ─── Consistency (has ↔ get) ────────────────────────────────────────

    #[Test]
    public function consistency(): void
    {
        $this->forAll(
            Generators::associative([
                'intVal'   => Generators::int(),
                'floatVal' => Generators::float(),
                'strVal'   => Generators::string(),
                'boolVal'  => Generators::bool(),
            ]),
        )->then(function (array $data): void {
            $dp = new ArrayDataProvider($data);

            $types = [
                'intVal'   => DataType::Int,
                'floatVal' => DataType::Float,
                'strVal'   => DataType::String,
                'boolVal'  => DataType::Bool,
            ];

            foreach ($types as $key => $type) {
                if ($dp->has($key, $type)) {
                    $val = $dp->getOptional($key, $type);
                    $this->assertNotNull($val, "has('$key', $type->name)==true but getOptional returned null");
                }
            }

            $this->assertFalse($dp->has('nonexistent_key_xyz'));
            try {
                $dp->getMandatory('nonexistent_key_xyz');
                $this->fail("getMandatory on missing key must throw");
            } catch (\Oasis\Mlib\Utils\Exceptions\MandatoryValueMissingException $e) {
                $this->assertSame('nonexistent_key_xyz', $e->getFieldName());
            }
        });
    }

    // ─── Path isolation (push/pop reversibility) ────────────────────────

    #[Test]
    public function pathIsolation(): void
    {
        $this->forAll(
            Generators::string(),
            Generators::string(),
        )->then(function (string $path1, string $path2): void {
            if ($path1 === '' || $path2 === '') return;
            $path1 = str_replace('.', '_', $path1);
            $path2 = str_replace('.', '_', $path2);

            $dp = new ArrayDataProvider(['a' => ['b' => 1]]);

            $dp->setCurrentPath($path1);
            $this->assertSame($path1, $dp->getCurrentPath());

            $dp->pushPath($path2);
            $this->assertSame($path1 . '.' . $path2, $dp->getCurrentPath());

            $dp->popPath();
            $this->assertSame($path1, $dp->getCurrentPath());

            $dp->setCurrentPath('');
            $this->assertSame('', $dp->getCurrentPath());
        });
    }
}
