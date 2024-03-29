<?php
declare(strict_types=1);

namespace Attlaz\Model;

class AdapterConnection
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

    public function getName(): string
    {
        return $this->data['name'];
    }

    public function getAdapterId(): string
    {
        return $this->data['adapter'];
    }


}
