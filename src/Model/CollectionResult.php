<?php
declare(strict_types=1);

namespace Attlaz\Model;

/**
 * A single page of a cursor-paginated collection. Mirrors the JS client's
 * CollectionResult: `getData()` returns the page's items, `$hasMore` indicates
 * whether further pages exist.
 *
 * @template T
 */
class CollectionResult
{
    /** @var T[] */
    private array $data;
    public bool $hasMore;

    /**
     * @param T[] $data
     */
    public function __construct(array $data = [], bool $hasMore = false)
    {
        $this->data = $data;
        $this->hasMore = $hasMore;
    }

    /**
     * @return T[]
     */
    public function getData(): array
    {
        return $this->data;
    }
}
