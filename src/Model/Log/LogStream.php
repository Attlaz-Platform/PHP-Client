<?php
declare(strict_types=1);

namespace Attlaz\Model\Log;

class LogStream
{
    private LogStreamId $id;
    private string $name;

    public function __construct(LogStreamId $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): LogStreamId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

}
