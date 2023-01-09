<?php
declare(strict_types=1);

namespace Attlaz\Model\Log;

use DateTimeInterface;

class LogEntry implements \JsonSerializable
{
    public ?string $id;
    private LogStreamId $logStreamId;
    private \DateTimeInterface $date;
    private string $level;
    private string $message;
    public array $context = [];
    public array $tags = [];

    public function __construct(LogStreamId $logStreamId, string $message, string $level, \DateTimeInterface $date)
    {
        $this->logStreamId = $logStreamId;
        $this->message = $message;
        $this->level = $level;
        $this->date = $date;
    }

    public function getLogStreamId(): LogStreamId
    {
        return $this->logStreamId;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function jsonSerialize(): array
    {
        return [
            'logStream' => ['id' => $this->logStreamId->__toString()],
            'date'      => $this->date->format(DateTimeInterface::RFC3339_EXTENDED),
            'level'     => $this->level,
            'message'   => $this->message,
            'context'   => $this->context,
            'tags'      => $this->tags,
        ];
    }
}
