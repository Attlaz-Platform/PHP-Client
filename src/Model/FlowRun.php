<?php
declare(strict_types=1);

namespace Attlaz\Model;

use Attlaz\Model\Log\LogStreamId;

class FlowRun
{
    public string $id;
    public string $flowId;
    public string $projectEnvironmentId;
    public LogStreamId $logStreamId;
    public array $arguments;
}
