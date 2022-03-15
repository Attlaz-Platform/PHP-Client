<?php
declare(strict_types=1);

namespace Attlaz\Model;

class LogStreamId
{
    private $id;


    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

}
