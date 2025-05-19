<?php
declare(strict_types=1);

namespace Attlaz\Model\Log;

class LogStreamId
{
    public function __construct(private readonly string $id)
    {
    }

    public function __toString(): string
    {
        return $this->id;
    }

}
