<?php
declare(strict_types=1);

namespace Attlaz\Model;

class LogEntry implements \JsonSerializable
{
    public $id;
    /** @var \DateTime */
    public $date;
    public $level;
    public $message;
    public $type;
    public $context = [];

    public function __construct(string $message, string $level)
    {
        $this->message = $message;
        $this->level = $level;
    }

    public function jsonSerialize(): array
    {
        return [
            'date'    => $this->date->format(\DateTime::RFC3339_EXTENDED),
            'level'   => $this->level,
            'type'    => $this->type,
            'message' => $this->message,
            'context' => $this->context
        ];
    }
}
