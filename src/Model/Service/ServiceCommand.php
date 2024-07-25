<?php
declare(strict_types=1);

namespace Attlaz\Model\Service;

class ServiceCommand
{
    public string $service;
    public string $command;
    private array $arguments = [];

    public function addArgument(string $key, string $value): void
    {
        $this->arguments[] = [
            'key' => $key,
            'value' => $value,
        ];
    }

    public function toJson(): array
    {
        return [
            'service' => $this->service,
            'command' => $this->command,
            'arguments' => $this->arguments,
        ];
    }
}
