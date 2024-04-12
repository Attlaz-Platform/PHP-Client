<?php
declare(strict_types=1);

namespace Attlaz\Model;

class FlowRunSummary
{
    public string $id;
    public string $flowId;
    // public string $name;

    public \DateTime $time;
    public float $runDuration;
    public float $pendingDuration;
    public string $status;
}
