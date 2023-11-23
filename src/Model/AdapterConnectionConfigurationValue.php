<?php
declare(strict_types=1);

namespace Attlaz\Model;

class AdapterConnectionConfigurationValue
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getId(): string
    {
        return $this->data['id'];
    }

    public function getConfigId(): string
    {
        return $this->data['config'];
    }

    public function getConfigKey(): string
    {
        return $this->data['config_key'];
    }

    public function getValue(): mixed
    {
        return $this->data['value'];
    }
}
