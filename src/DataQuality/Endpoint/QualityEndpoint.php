<?php
declare(strict_types=1);

namespace Attlaz\DataQuality\Endpoint;

use Attlaz\DataQuality\Model\ReportEntity;
use Attlaz\DataQuality\Model\ReportResult;
use Attlaz\DataQuality\Model\Scan;
use Attlaz\Endpoint\Endpoint;

/**
 * Data Quality ingest — reporting problems about entities into a dataset.
 *
 * Two ways to report. Either bracket a run yourself (`openScan` → `reportToScan` … → `completeScan`),
 * which lets you send entities in batches and is what a long-running job wants; or call
 * `reportProblems` once with everything, which opens, reports and completes in a single request.
 *
 * Either way, completing the run is what resolves problems that were checked and are no longer
 * there. Reporting without ever completing leaves earlier problems open.
 */
class QualityEndpoint extends Endpoint
{
    /**
     * Open a scan. `pack` names the group of checks this run covers and scopes what completing it is
     * allowed to close, so a partial run cannot resolve problems it never looked at.
     */
    public function openScan(string $datasetId, string|null $pack = null): Scan
    {
        $response = $this->requestObject('/data-quality/datasets/' . $datasetId . '/scans', ['pack' => $pack], 'POST');
        if ($response === null) {
            throw new \RuntimeException('Unable to open scan: dataset "' . $datasetId . '" not found');
        }

        return self::parseScan($response);
    }

    /**
     * Report what an open scan found. Safe to call repeatedly to send entities in batches.
     *
     * @param ReportEntity[] $entities
     */
    public function reportToScan(string $datasetId, string $scanId, array $entities): ReportResult
    {
        return $this->postReport(
            '/data-quality/datasets/' . $datasetId . '/scans/' . $scanId . '/report',
            ['entities' => self::toPayload($entities)],
        );
    }

    /**
     * Close the scan, resolving problems the run covered but did not re-report. Returns how many were
     * resolved.
     */
    public function completeScan(string $datasetId, string $scanId): int
    {
        $response = $this->requestObject('/data-quality/datasets/' . $datasetId . '/scans/' . $scanId . '/complete', [], 'POST');

        return (int)($response['resolved'] ?? 0);
    }

    /**
     * Open a scan, report everything and complete it in one call — for a reporter that already has
     * the full picture in memory and does not need to stream it in batches.
     *
     * @param ReportEntity[] $entities
     */
    public function reportProblems(string $datasetId, array $entities, string|null $pack = null): ReportResult
    {
        return $this->postReport(
            '/data-quality/datasets/' . $datasetId . '/problems',
            ['pack' => $pack, 'entities' => self::toPayload($entities)],
        );
    }

    /**
     * @param array<string,mixed> $body
     */
    private function postReport(string $uri, array $body): ReportResult
    {
        $response = $this->requestObject($uri, $body, 'POST');

        return new ReportResult(
            (int)($response['entity_count'] ?? 0),
            (int)($response['problem_count'] ?? 0),
            (int)($response['resolved_count'] ?? 0),
        );
    }

    /**
     * @param ReportEntity[] $entities
     * @return array<int,array<string,mixed>>
     */
    private static function toPayload(array $entities): array
    {
        return array_values(array_map(static fn(ReportEntity $entity): array => $entity->toArray(), $entities));
    }

    /**
     * @param array<string,mixed> $record
     */
    private static function parseScan(array $record): Scan
    {
        $scan = new Scan((string)$record['id'], (string)$record['dataset'], (string)$record['status']);
        $scan->pack = isset($record['pack']) ? (string)$record['pack'] : null;
        $scan->startedAt = isset($record['started_at']) ? new \DateTimeImmutable((string)$record['started_at']) : null;
        $scan->completedAt = isset($record['completed_at']) ? new \DateTimeImmutable((string)$record['completed_at']) : null;

        return $scan;
    }
}
