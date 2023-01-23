<?php
declare(strict_types=1);

namespace Attlaz\Model;

class RunFlowResult
{
    private bool $success;
    private string $id;
    public $result;

    public function __construct(bool $success, string $id)
    {
        $this->success = $success;
        $this->id = $id;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getId(): string
    {
        return $this->id;
    }

}
