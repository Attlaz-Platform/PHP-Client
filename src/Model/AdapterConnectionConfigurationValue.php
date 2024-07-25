<?php
declare(strict_types=1);

namespace Attlaz\Model;

class AdapterConnectionConfigurationValue
{
    public function __construct(private readonly array $data)
    {
    }

    public function getId(): string
    {
        return $this->data['id'];
    }

    public function getAdapterConnectionId(): string
    {
        return $this->data['adapter_connection'];
    }

    public function getAdapterConfigurationId(): string
    {
        return $this->data['configuration'];
    }

    public function getValue(): mixed
    {
        return $this->data['value'];
    }
}
