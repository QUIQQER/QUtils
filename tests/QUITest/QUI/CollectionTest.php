<?php

namespace QUITest\QUI;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use QUI\Collection;
use QUI\Exception;
use stdClass;

class CollectionTest extends TestCase
{
    public function testCollectionOperations(): void
    {
        $Collection = Collection::getInstance(['children' => ['b', 'c']]);
        $Collection->insert('a', 0);
        $Collection->insert('d', false);

        $this->assertSame('a', $Collection->first());
        $this->assertSame('d', $Collection->last());

        $Collection->append('keyed', 8);
        $this->assertSame('keyed', $Collection->get(8));
        $this->assertSame('keyed', $Collection->last());
        $this->assertTrue($Collection->contains('c'));
        $this->assertTrue($Collection->isNotEmpty());
        $this->assertFalse($Collection->isEmpty());
        $this->assertSame(5, $Collection->count());

        $visited = [];
        $Collection->each(static function (mixed $value, int $key) use (&$visited): void {
            $visited[$key] = $value;
        });
        $this->assertSame($Collection->toArray(), $visited);

        $this->assertSame([0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D', 8 => 'KEYED'], $Collection->map('strtoupper'));

        $Collection->sort(static fn(string $left, string $right): int => strcmp($left, $right));
        $this->assertSame('a', $Collection->first());

        $Filtered = $Collection->filter(static fn(string $value): bool => strlen($value) === 1);
        $this->assertInstanceOf(Collection::class, $Filtered);
        $this->assertSame(['a', 'b', 'c', 'd'], $Filtered->toArray());
        $this->assertInstanceOf(ArrayIterator::class, $Collection->getIterator());
    }

    public function testMergeAndArrayAccess(): void
    {
        $Object = new stdClass();
        $Collection = new Collection(['one']);
        $AllowedCollection = new class ([$Object]) extends Collection {
            protected array $allowed = [stdClass::class => stdClass::class];
        };
        $Collection->merge(new Collection(['two']), $AllowedCollection);
        $this->assertSame(['one', 'two', $Object], $Collection->toArray());

        $Collection[] = 'three';
        $Collection[5] = 'five';
        $this->assertTrue(isset($Collection[5]));
        $this->assertSame('five', $Collection[5]);
        unset($Collection[5]);
        $this->assertNull($Collection[5]);
    }

    public function testAllowedTypesAreEnforced(): void
    {
        $Allowed = new class () extends Collection {
            protected array $allowed = [stdClass::class => stdClass::class];
        };
        $Allowed->append('not allowed');
        $Allowed->append(new stdClass());

        $this->assertSame(1, $Allowed->length());
        $this->assertInstanceOf(stdClass::class, $Allowed->first());
    }

    public function testEmptyCollectionExceptionsAndClear(): void
    {
        $Collection = new Collection(['value']);
        $Collection->clear();

        $this->assertTrue($Collection->isEmpty());

        try {
            $Collection->first();
            $this->fail('first() must reject an empty collection.');
        } catch (Exception) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(Exception::class);
        $Collection->get(1);
    }
}
