<?php
declare(strict_types=1);

namespace Attlaz\DataQuality\Endpoint;

use Attlaz\DataQuality\Model\ReportEntity;
use Attlaz\DataQuality\Model\ReportResult;
use Attlaz\DataQuality\Model\Scan;
use Attlaz\Endpoint\Endpoint;
use Attlaz\Http\Path;
use Attlaz\Model\Exception\RequestException;

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
        $path = Path::build('/data-quality/datasets/:datasetId/scans', ['datasetId' => $datasetId]);
        $response = $this->requestObject($path, ['pack' => $pack], 'POST');
        if ($response === null) {
            throw self::writeFailed($path);
        }

        return self::parseScan($response);
    }

    /**
     * Report what an open scan found. Safe to call repeatedly to send entities in batches.
     *
     * **Batch size**: the API caps a request body at 512 KB (the Gateway rejects over 1 MB before it
     * even arrives), and an entity carrying one problem is roughly 250-300 bytes of JSON — so keep
     * batches to a low four figures of entities. An oversize request comes back as `413` with error
     * code `payload_too_large`. Send several batches into the same scan rather than one large one.
     *
     * @param ReportEntity[] $entities
     */
    public function reportToScan(string $datasetId, string $scanId, array $entities): ReportResult
    {
        return $this->postReport(
            Path::build('/data-quality/datasets/:datasetId/scans/:scanId/report', ['datasetId' => $datasetId, 'scanId' => $scanId]),
            ['entities' => self::toPayload($entities)],
        );
    }

    /**
     * Close the scan, resolving problems the run covered but did not re-report. Returns how many were
     * resolved.
     */
    public function completeScan(string $datasetId, string $scanId): int
    {
        $path = Path::build('/data-quality/datasets/:datasetId/scans/:scanId/complete', ['datasetId' => $datasetId, 'scanId' => $scanId]);
        // No body: the endpoint reads nothing from it, and an empty array would encode as `[]`.
        $response = $this->requestObject($path, null, 'POST');
        if ($response === null) {
            throw self::writeFailed($path);
        }

        return (int)($response['resolved'] ?? 0);
    }

    /**
     * Open a scan, report everything and complete it in one call — for a reporter that already has
     * the full picture in memory and does not need to stream it in batches.
     *
     * Everything goes in one request, so this is bounded by the API's 512 KB body cap — a few
     * thousand entities at most. Beyond that use openScan() / reportToScan() / completeScan(), which
     * can send as many batches as you like into one scan.
     *
     * @param ReportEntity[] $entities
     */
    public function reportProblems(string $datasetId, array $entities, string|null $pack = null): ReportResult
    {
        return $this->postReport(
            Path::build('/data-quality/datasets/:datasetId/problems', ['datasetId' => $datasetId]),
            ['pack' => $pack, 'entities' => self::toPayload($entities)],
        );
    }

    /**
     * @param array<string,mixed> $body
     */
    private function postReport(string $uri, array $body): ReportResult
    {
        $response = $this->requestObject($uri, $body, 'POST');
        if ($response === null) {
            throw self::writeFailed($uri);
        }

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
     * A write that came back with nothing — the base Endpoint maps a 404 to null.
     *
     * Reads may legitimately answer "no such thing" with null, but a write cannot: returning a zero
     * count here would be indistinguishable from a report that genuinely changed nothing, and a
     * reporter would log success while its problems went nowhere.
     *
     * The message names both causes because the API cannot yet tell them apart — a missing dataset
     * and a path that no longer exists (a reporter still on `/data_quality/`) both come back as a
     * bare 404. Once the API sets an error code, branch on that instead.
     */
    private static function writeFailed(string $uri): RequestException
    {
        $exception = new RequestException(
            'Data Quality write to "' . $uri . '" returned nothing. Either the dataset or scan does not exist, '
            . 'or the endpoint does not — check the id, and that the client version matches the API.',
        );
        $exception->httpCode = 404;

        return $exception;
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
