<?php
declare(strict_types=1);

namespace Attlaz\Model;

/**
 * Cursor pagination input for list endpoints. Mirrors the JS client's CursorPagination:
 * set `limit` and either `startingAfter` (next page) or `endingBefore` (previous page) to
 * the id of the boundary record. Serialised to the `limit` / `starting_after` /
 * `ending_before` query parameters.
 */
class CursorPagination
{
    public int|null $limit = null;
    public string|null $startingAfter = null;
    public string|null $endingBefore = null;
}
