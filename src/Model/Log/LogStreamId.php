<?php
declare(strict_types=1);

namespace Attlaz\Model\Log;

class LogStreamId
{
    private string $id;


    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function __toString(): string
    {
        return $this->id;
    }

}
