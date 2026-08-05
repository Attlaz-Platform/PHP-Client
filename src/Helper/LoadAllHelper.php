<?php
declare(strict_types=1);

namespace Attlaz\Helper;

use Attlaz\Model\CollectionResult;
use Attlaz\Model\CursorPagination;

/**
 * Page through a cursor-paginated endpoint method until exhausted, returning every record.
 * Mirrors the JS client's LoadAllHelper.
 */
class LoadAllHelper
{
    /**
     * @template T
     * @param callable(CursorPagination): CollectionResult<T> $call fetches one page for the given pagination
     * @param (callable(T): string)|null $idExtractor returns a record's cursor id; defaults to the public `id`
     *                                                 property. Pass e.g. fn($r) => $r->getId() for getter-style models.
     * @param int $limit page size used while iterating
     * @return array<int, T>
     * @throws \Exception when the collection exceeds 50,000 records (not intended for large datasets)
     */
    public static function loadAll(callable $call, callable|null $idExtractor = null, int $limit = 100): array
    {
        $idExtractor ??= static fn($record): string => (string)$record->id;

        $totalResult = [];
        $hasMore = true;
        $lastRef = null;
        while ($hasMore) {
            $pagination = new CursorPagination();
            $pagination->limit = $limit;
            $pagination->startingAfter = $lastRef;

            $page = $call($pagination);
            $records = $page->getData();

            // An empty page that still claims has_more cannot be paged past: the cursor advances from
            // the last record, so the next call would repeat this one forever. The server contradicted
            // itself — say so rather than spin.
            if (count($records) === 0 && $page->hasMore) {
                throw new \Exception('loadAll stopped after ' . count($totalResult) . ' records: the API reported more results but returned an empty page, so paging cannot continue.');
            }

            if (count($totalResult) + count($records) > 50000) {
                throw new \Exception('loadAll exceeded 50,000 records (' . (count($totalResult) + count($records)) . '). This method is not intended for large datasets.');
            }

            foreach ($records as $record) {
                $totalResult[] = $record;
            }
            if (count($records) > 0) {
                $lastRef = $idExtractor($records[count($records) - 1]);
            }

            $hasMore = $page->hasMore;
        }

        return $totalResult;
    }
}