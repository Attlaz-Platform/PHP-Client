<?php
declare(strict_types=1);

namespace Attlaz\Model;

class AdapterConfiguration
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

    public function getKey(): string
    {
        return $this->data['key'];
    }

    public function getType(): string
    {
        return $this->data['type'];
    }

    public function getAdapterId(): string
    {
        return $this->data['adapter'];
    }

    public function getName(): string
    {
        return $this->data['name'];
    }

    public function getDescription(): string
    {
        return $this->data['description'];
    }
}
