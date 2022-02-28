<?php
declare(strict_types=1);

namespace Attlaz\Model;

class LogEntry implements \JsonSerializable
{
    public ?string $id;
    public LogStreamId $logStreamId;
    public \DateTimeInterface $date;
    public string $level;
    public string $message;
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

    public function jsonSerialize(): array
    {
        return [
            'logStream' => ['id' => $this->logStreamId->getId()],
            'date'      => $this->date->format(\DateTime::RFC3339_EXTENDED),
            'level'     => $this->level,
            'message'   => $this->message,
            'context'   => $this->context,
            'tags'      => $this->tags,
        ];
    }
}
