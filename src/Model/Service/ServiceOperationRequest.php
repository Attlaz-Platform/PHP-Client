<?php
declare(strict_types=1);

namespace Attlaz\Model\Service;

class ServiceOperationRequest
{

    private array $arguments = [];

    public function __construct(
        public readonly string $connectionId,
        public readonly string $operation
    )
    {

    }

    public function addArgument(string $key, mixed $value): void
    {
        $this->arguments[$key] = $value;
    }

    public function toJson(): array
    {
        return [
            'connection' => $this->connectionId,
            'operation' => $this->operation,
            'params' => $this->arguments,
            'version' => '1.0.0',
        ];
    }
}
