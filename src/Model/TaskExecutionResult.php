<?php
declare(strict_types=1);

namespace Attlaz\Model;

class TaskExecutionResult
{
    private $success;
    private $id;
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
