<?php
declare(strict_types=1);

namespace Attlaz\DataQuality\Model;

/**
 * One reporting run against a dataset.
 *
 * A scan brackets the problems a reporter sends: open one, report into it, complete it. Completing
 * is what lets the platform resolve problems that were not re-reported, so a scan that is opened and
 * never completed leaves stale problems open.
 */
class Scan
{
    /** The check pack this run covers; scopes what completing the scan is allowed to resolve. */
    public string|null $pack = null;
    public \DateTimeImmutable|null $startedAt = null;
    public \DateTimeImmutable|null $completedAt = null;

    public function __construct(public string $id, public string $dataset, public string $status)
    {
    }
}
