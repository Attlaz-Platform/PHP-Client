<?php
declare(strict_types=1);

namespace Attlaz\DataQuality\Model;

/** What a report call did: how much was ingested, and how much it resolved. */
class ReportResult
{
    public function __construct(
        public int $entityCount = 0,
        public int $problemCount = 0,
        /** Problems closed because the reporter said the code now passes on that entity. */
        public int $resolvedCount = 0,
    ) {
    }
}
