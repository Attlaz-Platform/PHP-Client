<?php
declare(strict_types=1);

namespace Attlaz\Model;

class LogEntry implements \JsonSerializable
{
    public $id;
    private $logStreamId;
    private $date;
    private $level;
    private $message;
    public $context = [];
    public $tags = [];

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
            'log_stream' => $this->logStreamId->getId(),
            'date'       => $this->date->format(\DateTime::RFC3339_EXTENDED),
            'level'      => $this->level,
            'message'    => $this->message,
            'context'    => $this->context,
            'tags'       => $this->tags,
        ];
    }
}
