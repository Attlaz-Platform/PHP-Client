<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Http\Path;
use PHPUnit\Framework\TestCase;

/**
 * Unlike the rest of this suite these need no credentials and no network.
 */
class PathTest extends TestCase
{
    public function testFillsPlaceholders(): void
    {
        $this->assertSame(
            '/data-quality/datasets/abc123/scans',
            Path::build('/data-quality/datasets/:datasetId/scans', ['datasetId' => 'abc123']),
        );
    }

    public function testFillsSeveralPlaceholders(): void
    {
        $this->assertSame('/a/one/b/two', Path::build('/a/:first/b/:second', ['first' => 'one', 'second' => 'two']));
    }

    public function testAcceptsTemplateWithoutPlaceholders(): void
    {
        $this->assertSame('/data-quality/datasets', Path::build('/data-quality/datasets'));
    }

    public function testAcceptsNumbers(): void
    {
        $this->assertSame('/items/12', Path::build('/items/:id', ['id' => 12]));
        // 0 is a value, not an absent one.
        $this->assertSame('/items/0', Path::build('/items/:id', ['id' => 0]));
    }

    /**
     * The reason this helper exists: a raw value would otherwise change which endpoint is called.
     */
    public function testEncodesCharactersThatWouldChangeThePath(): void
    {
        $this->assertSame('/x/a%2F..%2Fb', Path::build('/x/:id', ['id' => 'a/../b']));
        $this->assertSame('/x/a%3Fb%3D1', Path::build('/x/:id', ['id' => 'a?b=1']));
        $this->assertSame('/x/a%23b', Path::build('/x/:id', ['id' => 'a#b']));
        $this->assertSame('/x/a%20b', Path::build('/x/:id', ['id' => 'a b']));
    }

    public function testRejectsEmptyValue(): void
    {
        // Silently building `/x/` would address the collection instead of the item.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/is empty/');

        Path::build('/x/:id', ['id' => '']);
    }

    public function testRejectsMissingValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/no value for ":id"/');

        Path::build('/x/:id', []);
    }

    public function testRejectsValueWithoutPlaceholder(): void
    {
        // A typo would otherwise vanish silently.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/no placeholder for "typo"/');

        Path::build('/x/:id', ['id' => 'a', 'typo' => 'b']);
    }
}
