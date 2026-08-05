<?php
declare(strict_types=1);

namespace Attlaz\DataQuality\Model;

/**
 * One entity being reported on: its identity, optionally what it is, and what was found.
 *
 * `entityType` + `entityId` are the natural key within the dataset — an entity is created on first
 * report, so there is nothing to register up front. `name`, `url` and `imageUrl` are optional: send
 * them and the dashboard can show a recognisable row instead of a bare identifier.
 *
 * `passed` carries codes that were *checked and found clean*. That is what lets the platform close a
 * problem that is no longer there — an entity reported with no problems and no `passed` codes says
 * nothing about the codes it was previously failing.
 */
class ReportEntity
{
    public string|null $name = null;
    public string|null $url = null;
    public string|null $imageUrl = null;
    /** @var array<string,mixed>|null */
    public array|null $data = null;
    /** @var ReportProblem[] */
    public array $problems = [];
    /** @var string[] */
    public array $passed = [];

    public function __construct(public string $entityType, public string $entityId)
    {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'name' => $this->name,
            'url' => $this->url,
            'image_url' => $this->imageUrl,
            'data' => $this->data,
            'problems' => array_map(static fn(ReportProblem $problem): array => $problem->toArray(), $this->problems),
            'passed' => $this->passed,
        ];
    }
}
